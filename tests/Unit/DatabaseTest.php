<?php
// tests/Unit/DatabaseTest.php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $this->db = new Database();
        // Ensure we're using SQLite
        $this->assertEquals('sqlite', $this->db->getDriver());
    }

    public function testSelectAllCrops(): void {
        $crops = $this->db->select('crop', 'crop_id,crop_name');
        $this->assertIsArray($crops);
        $this->assertCount(10, $crops);
    }

    public function testSelectCropById(): void {
        $crop = $this->db->find('crop', 1, 'crop_id');
        $this->assertNotNull($crop);
        $this->assertEquals('Rice', $crop['crop_name']);
        $this->assertEquals('NSIC Rc 222', $crop['variety']);
    }

    public function testSelectWithFilter(): void {
        $crops = $this->db->select('crop', 'crop_id,crop_name', ['crop_category=eq.Vegetable']);
        $this->assertIsArray($crops);
        $this->assertGreaterThanOrEqual(2, count($crops));
        foreach ($crops as $c) {
            // Verify they're actually vegetables by checking the DB
            $full = $this->db->find('crop', $c['crop_id'], 'crop_id');
            $this->assertEquals('Vegetable', $full['crop_category']);
        }
    }

    public function testSelectWithUrlEncodedEqValue(): void {
        // Callers URL-encode filter values (e.g. spaces as %20) because the
        // Supabase/PostgREST backend expects encoded query strings. The SQLite
        // backend must decode them too, or multi-word values never match.
        $farms = $this->db->select('farm', 'farm_id,farm_name,irrigation_type', [
            'irrigation_type=eq.' . rawurlencode('Drip Irrigation'),
        ]);
        $this->assertCount(3, $farms);
        foreach ($farms as $f) {
            $this->assertEquals('Drip Irrigation', $f['irrigation_type']);
        }
    }

    public function testSelectWithUrlEncodedOrFilter(): void {
        $farms = $this->db->select('farm', 'farm_id,farm_name', [
            'or=(farm_name.eq.' . rawurlencode('Castillo Agricultural Farm') . ',location.eq.Nowhere)',
        ]);
        $this->assertCount(1, $farms);
        $this->assertEquals('Castillo Agricultural Farm', $farms[0]['farm_name']);
    }

    public function testSelectWithIlikeFilter(): void {
        $farmers = $this->db->select('farmer', 'farmer_id,first_name', ['first_name=ilike.%Juan%']);
        $this->assertIsArray($farmers);
        $this->assertGreaterThanOrEqual(1, count($farmers));
        $this->assertEquals('Juan', $farmers[0]['first_name']);
    }

    public function testSelectWithOrFilter(): void {
        $farmers = $this->db->select('farmer', 'farmer_id,first_name,last_name', [
            'or=(first_name.ilike.%Juan%,last_name.ilike.%Cruz%)',
        ]);
        $this->assertIsArray($farmers);
        $this->assertGreaterThanOrEqual(1, count($farmers));
    }

    public function testSelectWithLimitAndOffset(): void {
        $page1 = $this->db->select('planting', 'planting_id', [], ['limit' => 5, 'offset' => 0, 'order' => 'planting_id.asc']);
        $page2 = $this->db->select('planting', 'planting_id', [], ['limit' => 5, 'offset' => 5, 'order' => 'planting_id.asc']);
        $this->assertCount(5, $page1);
        $this->assertCount(5, $page2);
        $this->assertNotEquals($page1[0]['planting_id'], $page2[0]['planting_id']);
    }

    public function testSelectNestedRelation(): void {
        $plantings = $this->db->select('planting', '*,farm(farm_name),crop(crop_name)', [], ['limit' => 1]);
        $this->assertIsArray($plantings);
        $this->assertNotEmpty($plantings);
        $this->assertArrayHasKey('farm', $plantings[0]);
        $this->assertArrayHasKey('crop', $plantings[0]);
        $this->assertNotNull($plantings[0]['farm']);
        $this->assertNotNull($plantings[0]['crop']);
        $this->assertArrayHasKey('farm_name', $plantings[0]['farm']);
        $this->assertArrayHasKey('crop_name', $plantings[0]['crop']);
    }

    public function testSelectNestedOneToOne(): void {
        $harvests = $this->db->select('harvest', '*,planting(planting_id,farm(farm_name),crop(crop_name))', [], ['limit' => 1]);
        $this->assertIsArray($harvests);
        $this->assertNotEmpty($harvests);
        $h = $harvests[0];
        $this->assertArrayHasKey('planting', $h);
        $this->assertNotNull($h['planting']);
        $this->assertArrayHasKey('farm', $h['planting']);
        $this->assertArrayHasKey('crop', $h['planting']);
    }

    public function testInsertAndFind(): void {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'contact_number' => '09000000000',
            'address' => 'Test City',
            'date_registered' => '2026-01-01',
        ];
        $result = $this->db->insert('farmer', $data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('farmer_id', $result);
        $id = $result['farmer_id'];

        $found = $this->db->find('farmer', $id, 'farmer_id');
        $this->assertNotNull($found);
        $this->assertEquals('Test', $found['first_name']);
        $this->assertEquals('User', $found['last_name']);

        // Cleanup
        $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
    }

    public function testUpdate(): void {
        $data = [
            'first_name' => 'OrigFirst',
            'last_name' => 'OrigLast',
            'date_registered' => '2026-01-01',
        ];
        $result = $this->db->insert('farmer', $data);
        $id = $result['farmer_id'];

        $this->db->update('farmer', ['first_name' => 'UpdatedFirst'], ['farmer_id=eq.' . $id]);
        $found = $this->db->find('farmer', $id, 'farmer_id');
        $this->assertEquals('UpdatedFirst', $found['first_name']);
        $this->assertEquals('OrigLast', $found['last_name']);

        // Cleanup
        $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
    }

    public function testDelete(): void {
        $data = [
            'first_name' => 'ToDelete',
            'last_name' => 'User',
            'date_registered' => '2026-01-01',
        ];
        $result = $this->db->insert('farmer', $data);
        $id = $result['farmer_id'];

        $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
        $found = $this->db->find('farmer', $id, 'farmer_id');
        $this->assertNull($found);
    }

    public function testFindNonExistent(): void {
        $result = $this->db->find('farmer', 99999, 'farmer_id');
        $this->assertNull($result);
    }

    public function testAggregateCount(): void {
        $statusCounts = $this->db->select('planting', 'status,count');
        $this->assertIsArray($statusCounts);
        $this->assertNotEmpty($statusCounts);
        foreach ($statusCounts as $row) {
            $this->assertArrayHasKey('status', $row);
            $this->assertArrayHasKey('count', $row);
            $this->assertGreaterThan(0, $row['count']);
        }
    }

    public function testSelectAllFarms(): void {
        $farms = $this->db->select('farm', 'farm_id,farm_name');
        $this->assertIsArray($farms);
        $this->assertCount(15, $farms);
    }

    public function testSelectAllHarvests(): void {
        $harvests = $this->db->select('harvest', 'harvest_id,quantity_kg');
        $this->assertIsArray($harvests);
        $this->assertCount(20, $harvests);
    }

    public function testGroupByParentForOneToMany(): void {
        $farmers = $this->db->select('farmer', '*,farm(*)', [], ['limit' => 3]);
        $this->assertIsArray($farmers);
        $this->assertNotEmpty($farmers);
        // farmer should have 'farm' as an array (1:M)
        $this->assertArrayHasKey('farm', $farmers[0]);
        $this->assertIsArray($farmers[0]['farm']);
    }

    public function testMtoOneRelationIsObject(): void {
        $plantings = $this->db->select('planting', '*,farm(farm_name),crop(crop_name)', [], ['limit' => 1]);
        $this->assertIsArray($plantings);
        $this->assertNotEmpty($plantings);
        // farm and crop should be single objects, not arrays
        $this->assertIsArray($plantings[0]['farm']);
        $this->assertArrayHasKey('farm_name', $plantings[0]['farm']);
        $this->assertIsArray($plantings[0]['crop']);
        $this->assertArrayHasKey('crop_name', $plantings[0]['crop']);
        // Should NOT be a nested array
        $this->assertArrayNotHasKey(0, $plantings[0]['farm']);
    }
}
