<?php
// tests/Unit/DatabaseTest.php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    private Database $db;

    protected function setUp(): void {
        $this->db = new Database();
        // Ensure we're using Supabase
        $this->assertEquals('supabase', $this->db->getDriver());
    }

    public function testSelectAllCrops(): void {
        $crops = $this->db->select('crop', 'crop_id,species_id,variety');
        $this->assertIsArray($crops);
        $this->assertCount(10, $crops);
    }

    public function testSelectCropById(): void {
        $crop = $this->db->find('crop', 1, 'crop_id');
        $this->assertNotNull($crop);
        $this->assertEquals(1, (int) $crop['species_id']);
        $this->assertEquals('NSIC Rc 222', $crop['variety']);
        $this->assertEquals(1, (int) $crop['season_id']);
    }

    public function testCropNestsSpeciesAndCategory(): void {
        $crops = $this->db->select(
            'crop',
            '*,species(crop_name,crop_category(category_name)),season(season_name)',
            ['crop_id=eq.1'],
        );
        $this->assertCount(1, $crops);
        $crop = $crops[0];
        $this->assertEquals('Rice', $crop['species']['crop_name']);
        $this->assertEquals('Cereal', $crop['species']['crop_category']['category_name']);
        $this->assertEquals('Wet Season', $crop['season']['season_name']);
    }

    public function testSelectWithFilter(): void {
        // Vegetables (crop_category_id = 2) are Tomato (3) and Eggplant (4).
        $species = $this->db->select('species', 'species_id', ['crop_category_id=eq.2']);
        $ids = array_map('intval', array_column($species, 'species_id'));
        $this->assertNotEmpty($ids);

        $crops = $this->db->select('crop', 'crop_id,species_id', [
            'species_id=in.(' . implode(',', $ids) . ')',
        ]);
        $this->assertCount(2, $crops);
        foreach ($crops as $c) {
            $this->assertContains((int) $c['species_id'], $ids);
        }
    }

    public function testSelectWithUrlEncodedEqValue(): void {
        // Callers URL-encode filter values (e.g. spaces as %20) because the
        // Supabase/PostgREST backend expects encoded query strings; the client
        // must decode them so multi-word values match.
        // irrigation_type_id = 3 is "Drip Irrigation".
        $farms = $this->db->select('farm', 'farm_id,farm_name,irrigation_type(type_name)', [
            'irrigation_type_id=eq.3',
        ]);
        $this->assertCount(3, $farms);
        foreach ($farms as $f) {
            $this->assertEquals('Drip Irrigation', $f['irrigation_type']['type_name']);
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
        $farmers = $this->db->select('farmer', 'farmer_id,first_name', ['first_name=ilike.%25Juan%25']);
        $this->assertIsArray($farmers);
        $this->assertGreaterThanOrEqual(1, count($farmers));
        $this->assertEquals('Juan', $farmers[0]['first_name']);
    }

    public function testSelectWithOrFilter(): void {
        $farmers = $this->db->select('farmer', 'farmer_id,first_name,last_name', [
            'or=(first_name.ilike.%25Juan%25,last_name.ilike.%25Cruz%25)',
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
        $plantings = $this->db->select(
            'planting',
            '*,farm(farm_name),crop(variety,species(crop_name)),planting_status(status_name)',
            [],
            ['limit' => 1, 'order' => 'planting_id.asc'],
        );
        $this->assertIsArray($plantings);
        $this->assertNotEmpty($plantings);
        $p = $plantings[0];
        $this->assertArrayHasKey('farm', $p);
        $this->assertArrayHasKey('crop', $p);
        $this->assertArrayHasKey('planting_status', $p);
        $this->assertNotNull($p['farm']);
        $this->assertNotNull($p['crop']);
        $this->assertArrayHasKey('farm_name', $p['farm']);
        $this->assertEquals('Rice', $p['crop']['species']['crop_name']);
        $this->assertEquals('Harvested', $p['planting_status']['status_name']);
    }

    public function testSelectNestedOneToOne(): void {
        $harvests = $this->db->select(
            'harvest',
            '*,quality_grade(grade_name),planting(planting_id,farm(farm_name),crop(crop_id,variety))',
            [],
            ['limit' => 1, 'order' => 'harvest_id.asc'],
        );
        $this->assertIsArray($harvests);
        $this->assertNotEmpty($harvests);
        $h = $harvests[0];
        $this->assertArrayHasKey('planting', $h);
        $this->assertArrayHasKey('quality_grade', $h);
        $this->assertNotNull($h['planting']);
        $this->assertArrayHasKey('farm', $h['planting']);
        $this->assertArrayHasKey('crop', $h['planting']);
        $this->assertEquals('Grade A', $h['quality_grade']['grade_name']);
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
        $plantingModel = new Planting($this->db);
        $statusCounts = $plantingModel->byStatus();
        $this->assertIsArray($statusCounts);
        $this->assertNotEmpty($statusCounts);
        foreach ($statusCounts as $row) {
            $this->assertArrayHasKey('planting_status_id', $row);
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
        $plantings = $this->db->select('planting', '*,farm(farm_name),crop(variety)', [], ['limit' => 1]);
        $this->assertIsArray($plantings);
        $this->assertNotEmpty($plantings);
        // farm and crop should be single objects, not arrays
        $this->assertIsArray($plantings[0]['farm']);
        $this->assertArrayHasKey('farm_name', $plantings[0]['farm']);
        $this->assertIsArray($plantings[0]['crop']);
        $this->assertArrayHasKey('variety', $plantings[0]['crop']);
        // Should NOT be a nested array
        $this->assertArrayNotHasKey(0, $plantings[0]['farm']);
    }

    public function testLookupTablesArePopulated(): void {
        $expected = [
            'crop_category' => 5,
            'season' => 3,
            'soil_type' => 4,
            'irrigation_type' => 3,
            'planting_status' => 3,
            'quality_grade' => 3,
            'harvest_remarks' => 5,
        ];
        foreach ($expected as $table => $count) {
            $this->assertCount($count, $this->db->select($table, '*'), "$table lookup is seeded");
        }
    }

    public function testLookupRowsExposeIdAndLabel(): void {
        $grades = $this->db->select('quality_grade', 'quality_grade_id,grade_name', [], ['order' => 'quality_grade_id.asc']);
        $this->assertEquals(1, (int) $grades[0]['quality_grade_id']);
        $this->assertEquals('Grade A', $grades[0]['grade_name']);
    }

    public function testHarvestHasNoFreeTextRemarksColumn(): void {
        $rows = $this->db->select('harvest', '*', [], ['limit' => 1]);
        $this->assertArrayNotHasKey('remarks', $rows[0]);
    }
}
