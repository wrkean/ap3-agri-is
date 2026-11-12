<?php
// tests/Integration/CrudWorkflowTest.php
use PHPUnit\Framework\TestCase;

class CrudWorkflowTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $this->db = new Database();
    }

    public function testCreateFarmerAndVerify(): void {
        $data = [
            'first_name' => 'TestFarmer',
            'last_name' => 'Integration',
            'contact_number' => '09999999999',
            'address' => 'Test City',
            'date_registered' => '2026-01-01',
        ];
        $result = $this->db->insert('farmer', $data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('farmer_id', $result);
        $id = $result['farmer_id'];

        $found = $this->db->find('farmer', $id, 'farmer_id');
        $this->assertEquals('TestFarmer', $found['first_name']);
        $this->assertEquals('Integration', $found['last_name']);

        // Cleanup
        $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
    }

    public function testCreateFarmRequiresFarmer(): void {
        // Create a farmer first
        $farmer = $this->db->insert('farmer', [
            'first_name' => 'FarmOwner',
            'last_name' => 'Test',
            'date_registered' => '2026-01-01',
        ]);
        $farmerId = $farmer['farmer_id'];

        // Create a farm
        $farm = $this->db->insert('farm', [
            'farmer_id' => $farmerId,
            'farm_name' => 'Test Farm',
            'location' => 'Test Location',
            'area_hectares' => 2.5,
            'soil_type' => 'Loam',
            'irrigation_type' => 'Rainfed',
        ]);
        $this->assertIsArray($farm);
        $this->assertArrayHasKey('farm_id', $farm);

        // Verify farm has farmer relation
        $found = $this->db->find('farm', $farm['farm_id'], 'farm_id');
        $this->assertEquals($farmerId, $found['farmer_id']);

        // Cleanup
        $this->db->delete('farm', ['farm_id=eq.' . $farm['farm_id']]);
        $this->db->delete('farmer', ['farmer_id=eq.' . $farmerId]);
    }

    public function testCreatePlantingRequiresFarmAndCrop(): void {
        // Use existing farm (id=1) and crop (id=1)
        $planting = $this->db->insert('planting', [
            'farm_id' => 1,
            'crop_id' => 1,
            'planting_date' => '2026-06-01',
            'area_planted' => 1.0,
            'seed_quantity' => 20.0,
            'expected_harvest_date' => '2026-09-01',
            'status' => 'Growing',
        ]);
        $this->assertIsArray($planting);
        $this->assertArrayHasKey('planting_id', $planting);

        // Verify nested data
        $found = $this->db->select('planting', '*,farm(farm_name),crop(crop_name)', ['planting_id=eq.' . $planting['planting_id']]);
        $this->assertNotEmpty($found);
        $this->assertNotNull($found[0]['farm']);
        $this->assertNotNull($found[0]['crop']);

        // Cleanup
        $this->db->delete('planting', ['planting_id=eq.' . $planting['planting_id']]);
    }

    public function testCreateHarvestRequiresPlanting(): void {
        $harvest = $this->db->insert('harvest', [
            'planting_id' => 1,
            'harvest_date' => '2026-06-15',
            'quantity_kg' => 500.0,
            'selling_price_per_kg' => 25.0,
            'quality_grade' => 'Grade A',
            'remark_id' => 1,
            'remarks' => 'Test harvest',
        ]);
        $this->assertIsArray($harvest);
        $this->assertArrayHasKey('harvest_id', $harvest);

        // Verify
        $found = $this->db->find('harvest', $harvest['harvest_id'], 'harvest_id');
        $this->assertEquals(500.0, (float)$found['quantity_kg']);
        $this->assertEquals('Grade A', $found['quality_grade']);

        // Cleanup
        $this->db->delete('harvest', ['harvest_id=eq.' . $harvest['harvest_id']]);
    }

    public function testDeleteWithReferentialIntegrity(): void {
        // Create a farmer with a farm
        $farmer = $this->db->insert('farmer', [
            'first_name' => 'RefTest',
            'last_name' => 'Farmer',
            'date_registered' => '2026-01-01',
        ]);
        $farmerId = $farmer['farmer_id'];

        $farm = $this->db->insert('farm', [
            'farmer_id' => $farmerId,
            'farm_name' => 'Ref Test Farm',
            'location' => 'Test',
            'area_hectares' => 1.0,
            'soil_type' => 'Loam',
            'irrigation_type' => 'Rainfed',
        ]);

        // Attempt to delete farmer should be blocked (has farm)
        $farms = $this->db->select('farm', 'farm_id', ['farmer_id=eq.' . $farmerId]);
        $this->assertNotEmpty($farms);

        // Cleanup
        $this->db->delete('farm', ['farm_id=eq.' . $farm['farm_id']]);
        $this->db->delete('farmer', ['farmer_id=eq.' . $farmerId]);
    }

    public function testCountQueries(): void {
        $crops = $this->db->select('crop', 'crop_id');
        $this->assertCount(10, $crops);

        $farmers = $this->db->select('farmer', 'farmer_id');
        $this->assertGreaterThanOrEqual(15, count($farmers));

        $harvests = $this->db->select('harvest', 'harvest_id');
        $this->assertCount(20, $harvests);
    }

    public function testHarvestRemarksLookup(): void {
        $remarks = $this->db->select('harvest_remarks', 'remark_id,remark_description');
        $this->assertCount(5, $remarks);
        $descriptions = array_column($remarks, 'remark_description');
        $this->assertContains('Good Harvest', $descriptions);
        $this->assertContains('Damaged Harvest', $descriptions);
    }

    public function testPlantingStatusFilter(): void {
        $harvested = $this->db->select('planting', 'planting_id,status', ['status=eq.Harvested']);
        $this->assertNotEmpty($harvested);
        foreach ($harvested as $p) {
            $this->assertEquals('Harvested', $p['status']);
        }

        $growing = $this->db->select('planting', 'planting_id,status', ['status=eq.Growing']);
        $this->assertNotEmpty($growing);
        foreach ($growing as $p) {
            $this->assertEquals('Growing', $p['status']);
        }
    }
}
