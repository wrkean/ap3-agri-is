<?php
/**
 * SQLite database builder for AP3 Agriculture Information System.
 * Run: php database/build_sqlite.php
 * Creates: database/agri.db
 */

$dbPath = __DIR__ . '/agri.db';
if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

// Schema
$pdo->exec("
CREATE TABLE harvest_remarks (
    remark_id INTEGER PRIMARY KEY,
    remark_description TEXT NOT NULL
);

CREATE TABLE crop (
    crop_id INTEGER PRIMARY KEY AUTOINCREMENT,
    crop_name TEXT NOT NULL,
    variety TEXT NOT NULL,
    crop_category TEXT NOT NULL,
    growing_period_days INTEGER NOT NULL,
    recommended_season TEXT NOT NULL
);

CREATE TABLE farmer (
    farmer_id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    contact_number TEXT,
    address TEXT,
    date_registered TEXT NOT NULL DEFAULT (date('now')),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE farm (
    farm_id INTEGER PRIMARY KEY AUTOINCREMENT,
    farmer_id INTEGER NOT NULL REFERENCES farmer(farmer_id),
    farm_name TEXT NOT NULL,
    location TEXT NOT NULL,
    area_hectares REAL NOT NULL CHECK (area_hectares > 0),
    soil_type TEXT NOT NULL,
    irrigation_type TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE planting (
    planting_id INTEGER PRIMARY KEY AUTOINCREMENT,
    farm_id INTEGER NOT NULL REFERENCES farm(farm_id),
    crop_id INTEGER NOT NULL REFERENCES crop(crop_id),
    planting_date TEXT NOT NULL,
    area_planted REAL NOT NULL CHECK (area_planted > 0),
    seed_quantity REAL NOT NULL CHECK (seed_quantity >= 0),
    expected_harvest_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Growing',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE harvest (
    harvest_id INTEGER PRIMARY KEY AUTOINCREMENT,
    planting_id INTEGER NOT NULL REFERENCES planting(planting_id),
    harvest_date TEXT NOT NULL,
    quantity_kg REAL NOT NULL CHECK (quantity_kg > 0),
    selling_price_per_kg REAL NOT NULL CHECK (selling_price_per_kg >= 0),
    quality_grade TEXT NOT NULL,
    remark_id INTEGER REFERENCES harvest_remarks(remark_id),
    remarks TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
");

// Seed data
$pdo->exec("
INSERT INTO harvest_remarks (remark_id, remark_description) VALUES
(1,'Good Harvest'),(2,'Acceptable Quality'),(3,'Low Yield'),(4,'Poor Quality'),(5,'Damaged Harvest');
");

$crops = [
 [1,'Rice','NSIC Rc 222','Cereal',115,'Wet Season'],
 [2,'Corn','Yellow Corn','Cereal',100,'Dry Season'],
 [3,'Tomato','Diamante Max','Vegetable',90,'Dry Season'],
 [4,'Eggplant','Morena','Vegetable',100,'Year Round'],
 [5,'Banana','Lakatan','Fruit',300,'Year Round'],
 [6,'Mango','Carabao','Fruit',150,'Year Round'],
 [7,'Peanut','BPI Pn 9','Legume',110,'Year Round'],
 [8,'Cassava','Golden Yellow','Root Crop',240,'Year Round'],
 [9,'Sweet Potato','NSIC Sp 30','Root Crop',120,'Year Round'],
 [10,'Watermelon','Sweet 16','Fruit',85,'Dry Season'],
];
$stmt = $pdo->prepare("INSERT INTO crop (crop_id,crop_name,variety,crop_category,growing_period_days,recommended_season) VALUES (?,?,?,?,?,?)");
foreach ($crops as $c) $stmt->execute($c);

$farmers = [
 [1,'Juan','Dela Cruz','09171234567','Cebu City','2026-10-26'],
 [2,'Maria','Santos','09181234567','Carcar City, Cebu','2026-10-26'],
 [3,'Pedro','Reyes','09191234567','Danao City, Cebu','2026-10-26'],
 [4,'Ana','Garcia','09201234567','Argao, Cebu','2026-10-26'],
 [5,'Jose','Mendoza','09211234567','Bogo City, Cebu','2026-10-26'],
 [6,'Ramon','Flores','09221234567','Toledo City, Cebu','2026-10-26'],
 [7,'Elena','Cruz','09231234567','Naga City, Cebu','2026-10-26'],
 [8,'Antonio','Lim','09241234567','Minglanilla, Cebu','2026-10-26'],
 [9,'Rosa','Fernandez','09251234567','Liloan, Cebu','2026-10-26'],
 [10,'Mario','Villanueva','09261234567','Compostela, Cebu','2026-10-26'],
 [11,'Teresa','Navarro','09271234567','Talisay City, Cebu','2026-10-26'],
 [12,'Carlos','Ramos','09281234567','Consolacion, Cebu','2026-10-26'],
 [13,'Liza','Torres','09291234567','Dalaguete, Cebu','2026-10-26'],
 [14,'Roberto','Castillo','09301234567','Barili, Cebu','2026-10-26'],
 [15,'Susan','Aquino','09311234567','Sibonga, Cebu','2026-10-26'],
];
$stmt = $pdo->prepare("INSERT INTO farmer (farmer_id,first_name,last_name,contact_number,address,date_registered) VALUES (?,?,?,?,?,?)");
foreach ($farmers as $f) $stmt->execute($f);

$farms = [
 [1,1,'Dela Cruz Farm','Cebu City',2.50,'Loam','Rainfed'],
 [2,2,'Santos Rice Farm','Carcar City, Cebu',4.20,'Clay Loam','Irrigated'],
 [3,3,'Reyes Agricultural Farm','Danao City, Cebu',3.80,'Sandy Loam','Rainfed'],
 [4,4,'Garcia Vegetable Farm','Argao, Cebu',1.75,'Loam','Drip Irrigation'],
 [5,5,'Mendoza Farm','Bogo City, Cebu',5.00,'Clay','Irrigated'],
 [6,6,'Flores Corn Farm','Toledo City, Cebu',3.40,'Sandy Loam','Rainfed'],
 [7,7,'Cruz Family Farm','Naga City, Cebu',2.80,'Loam','Irrigated'],
 [8,8,'Lim Agricultural Farm','Minglanilla, Cebu',2.25,'Clay Loam','Drip Irrigation'],
 [9,9,'Fernandez Farm','Liloan, Cebu',3.10,'Sandy Loam','Rainfed'],
 [10,10,'Villanueva Green Farm','Compostela, Cebu',4.50,'Loam','Irrigated'],
 [11,11,'Navarro Rice Field','Talisay City, Cebu',3.75,'Clay','Irrigated'],
 [12,12,'Ramos Farm','Consolacion, Cebu',2.60,'Loam','Rainfed'],
 [13,13,'Torres Vegetable Farm','Dalaguete, Cebu',1.80,'Sandy Loam','Drip Irrigation'],
 [14,14,'Castillo Agricultural Farm','Barili, Cebu',5.50,'Clay Loam','Irrigated'],
 [15,15,'Aquino Farm','Sibonga, Cebu',3.20,'Loam','Rainfed'],
];
$stmt = $pdo->prepare("INSERT INTO farm (farm_id,farmer_id,farm_name,location,area_hectares,soil_type,irrigation_type) VALUES (?,?,?,?,?,?,?)");
foreach ($farms as $f) $stmt->execute($f);

$plantings = [
 [1,1,1,'2026-01-15',1.00,30.00,'2026-05-10','Harvested'],
 [2,2,2,'2026-01-20',1.20,35.00,'2026-04-29','Harvested'],
 [3,3,3,'2026-01-25',0.80,25.00,'2026-04-24','Harvested'],
 [4,4,4,'2026-02-01',0.90,28.00,'2026-05-11','Harvested'],
 [5,5,5,'2026-02-05',1.50,50.00,'2026-12-01','Harvested'],
 [6,6,1,'2026-02-10',0.60,20.00,'2026-06-05','Harvested'],
 [7,7,2,'2026-02-15',1.10,32.00,'2026-05-25','Harvested'],
 [8,8,3,'2026-02-20',0.70,22.00,'2026-05-20','Harvested'],
 [9,9,4,'2026-02-25',0.85,26.00,'2026-06-04','Harvested'],
 [10,10,6,'2026-03-01',1.30,40.00,'2026-07-29','Harvested'],
 [11,11,1,'2026-02-09',0.50,20.00,'2026-06-04','Harvested'],
 [12,12,2,'2026-02-12',0.60,25.00,'2026-05-23','Harvested'],
 [13,13,3,'2026-02-15',0.70,30.00,'2026-05-16','Harvested'],
 [14,14,4,'2026-02-18',0.80,35.00,'2026-05-29','Harvested'],
 [15,15,5,'2026-02-21',0.90,40.00,'2026-12-18','Harvested'],
 [16,1,6,'2026-02-24',1.00,45.00,'2026-07-24','Harvested'],
 [17,2,7,'2026-02-27',1.10,10.00,'2026-06-17','Harvested'],
 [18,3,8,'2026-03-02',1.20,15.00,'2026-10-28','Harvested'],
 [19,4,9,'2026-03-05',1.30,20.00,'2026-07-03','Harvested'],
 [20,5,10,'2026-03-08',1.40,25.00,'2026-06-01','Harvested'],
 [21,6,1,'2026-03-11',0.50,30.00,'2026-07-04','Growing'],
 [22,7,2,'2026-03-14',0.60,35.00,'2026-06-22','Growing'],
 [23,8,3,'2026-03-17',0.70,40.00,'2026-06-15','Growing'],
 [24,9,4,'2026-03-20',0.80,45.00,'2026-06-28','Growing'],
 [25,10,5,'2026-03-23',0.90,10.00,'2027-01-17','Growing'],
 [26,11,6,'2026-03-26',1.00,15.00,'2026-08-23','Growing'],
 [27,12,7,'2026-03-29',1.10,20.00,'2026-07-17','Growing'],
 [28,13,8,'2026-04-01',1.20,25.00,'2026-11-27','Growing'],
];
$stmt = $pdo->prepare("INSERT INTO planting (planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status) VALUES (?,?,?,?,?,?,?,?)");
foreach ($plantings as $p) $stmt->execute($p);

$harvests = [
 [1,1,'2026-05-07',900.00,22.00,'Grade A',1,'High quality harvest'],
 [2,2,'2026-04-25',1025.00,25.50,'Grade B',2,'Good harvest'],
 [3,3,'2026-04-18',1150.00,29.00,'Grade C',2,'Acceptable quality'],
 [4,4,'2026-05-01',1275.00,32.50,'Grade A',1,'High quality harvest'],
 [5,5,'2026-11-20',1400.00,36.00,'Grade B',2,'Good harvest'],
 [6,6,'2026-06-26',1525.00,39.50,'Grade C',2,'Acceptable quality'],
 [7,7,'2026-05-20',1650.00,43.00,'Grade A',1,'High quality harvest'],
 [8,8,'2026-09-30',1775.00,46.50,'Grade B',2,'Good harvest'],
 [9,9,'2026-06-05',1900.00,22.00,'Grade C',2,'Acceptable quality'],
 [10,10,'2026-05-04',2025.00,25.50,'Grade A',1,'High quality harvest'],
 [11,11,'2026-06-06',2150.00,29.00,'Grade B',2,'Good harvest'],
 [12,12,'2026-05-25',2275.00,32.50,'Grade C',2,'Acceptable quality'],
 [13,13,'2026-05-18',2400.00,36.00,'Grade A',1,'High quality harvest'],
 [14,14,'2026-05-31',2525.00,39.50,'Grade B',2,'Good harvest'],
 [15,15,'2026-12-20',2650.00,43.00,'Grade C',2,'Acceptable quality'],
 [16,16,'2026-07-26',2775.00,46.50,'Grade A',1,'High quality harvest'],
 [17,17,'2026-06-19',2900.00,22.00,'Grade B',2,'Good harvest'],
 [18,18,'2026-10-30',3025.00,25.50,'Grade C',2,'Acceptable quality'],
 [19,19,'2026-07-05',3150.00,29.00,'Grade A',1,'High quality harvest'],
 [20,20,'2026-06-03',3275.00,32.50,'Grade B',2,'Good harvest'],
];
$stmt = $pdo->prepare("INSERT INTO harvest (harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_grade,remark_id,remarks) VALUES (?,?,?,?,?,?,?,?)");
foreach ($harvests as $h) $stmt->execute($h);

// Fix autoincrement sequences
$pdo->exec("UPDATE sqlite_sequence SET seq = 10 WHERE name = 'crop'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 15 WHERE name = 'farmer'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 15 WHERE name = 'farm'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 28 WHERE name = 'planting'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 20 WHERE name = 'harvest'");

echo "SQLite database created at: $dbPath\n";
echo "Tables: " . implode(', ', $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN)) . "\n";
foreach (['crop','farmer','farm','planting','harvest','harvest_remarks'] as $t) {
    echo "$t: " . $pdo->query("SELECT count(*) FROM $t")->fetchColumn() . " rows\n";
}