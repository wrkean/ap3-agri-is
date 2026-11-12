<?php
/**
 * SQLite database builder for AP3 Agriculture Information System.
 * Run: php database/build_sqlite.php
 * Creates: database/agri.db
 *
 * Mirrors database/schema.sql (the Supabase/PostgreSQL schema) so the app
 * behaves identically on both backends.
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
CREATE TABLE crop_category (
    crop_category_id INTEGER PRIMARY KEY,
    category_name TEXT NOT NULL UNIQUE
);

CREATE TABLE season (
    season_id INTEGER PRIMARY KEY,
    season_name TEXT NOT NULL UNIQUE
);

CREATE TABLE soil_type (
    soil_type_id INTEGER PRIMARY KEY,
    type_name TEXT NOT NULL UNIQUE
);

CREATE TABLE irrigation_type (
    irrigation_type_id INTEGER PRIMARY KEY,
    type_name TEXT NOT NULL UNIQUE
);

CREATE TABLE planting_status (
    planting_status_id INTEGER PRIMARY KEY,
    status_name TEXT NOT NULL UNIQUE
);

CREATE TABLE quality_grade (
    quality_grade_id INTEGER PRIMARY KEY,
    grade_name TEXT NOT NULL UNIQUE
);

CREATE TABLE harvest_remarks (
    remark_id INTEGER PRIMARY KEY,
    remark_description TEXT NOT NULL
);

CREATE TABLE species (
    species_id INTEGER PRIMARY KEY AUTOINCREMENT,
    crop_name TEXT NOT NULL UNIQUE,
    crop_category_id INTEGER NOT NULL REFERENCES crop_category(crop_category_id)
);

CREATE TABLE crop (
    crop_id INTEGER PRIMARY KEY AUTOINCREMENT,
    species_id INTEGER NOT NULL REFERENCES species(species_id),
    variety TEXT NOT NULL,
    growing_period_days INTEGER NOT NULL CHECK (growing_period_days > 0),
    season_id INTEGER NOT NULL REFERENCES season(season_id),
    UNIQUE (species_id, variety)
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
    soil_type_id INTEGER NOT NULL REFERENCES soil_type(soil_type_id),
    irrigation_type_id INTEGER NOT NULL REFERENCES irrigation_type(irrigation_type_id),
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
    planting_status_id INTEGER NOT NULL REFERENCES planting_status(planting_status_id),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    CHECK (expected_harvest_date >= planting_date)
);

CREATE TABLE harvest (
    harvest_id INTEGER PRIMARY KEY AUTOINCREMENT,
    planting_id INTEGER NOT NULL REFERENCES planting(planting_id),
    harvest_date TEXT NOT NULL,
    quantity_kg REAL NOT NULL CHECK (quantity_kg > 0),
    selling_price_per_kg REAL NOT NULL CHECK (selling_price_per_kg >= 0),
    quality_grade_id INTEGER NOT NULL REFERENCES quality_grade(quality_grade_id),
    remark_id INTEGER REFERENCES harvest_remarks(remark_id),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
");

// Lookup data
$pdo->exec("
INSERT INTO crop_category (crop_category_id, category_name) VALUES
(1,'Cereal'),(2,'Vegetable'),(3,'Fruit'),(4,'Legume'),(5,'Root Crop');

INSERT INTO season (season_id, season_name) VALUES
(1,'Wet Season'),(2,'Dry Season'),(3,'Year Round');

INSERT INTO soil_type (soil_type_id, type_name) VALUES
(1,'Loam'),(2,'Clay Loam'),(3,'Sandy Loam'),(4,'Clay');

INSERT INTO irrigation_type (irrigation_type_id, type_name) VALUES
(1,'Rainfed'),(2,'Irrigated'),(3,'Drip Irrigation');

INSERT INTO planting_status (planting_status_id, status_name) VALUES
(1,'Growing'),(2,'Harvested'),(3,'Failed');

INSERT INTO quality_grade (quality_grade_id, grade_name) VALUES
(1,'Grade A'),(2,'Grade B'),(3,'Grade C');

INSERT INTO harvest_remarks (remark_id, remark_description) VALUES
(1,'Good Harvest'),(2,'Acceptable Quality'),(3,'Low Yield'),(4,'Poor Quality'),(5,'Damaged Harvest');
");

$species = [
    [1, 'Rice', 1],
    [2, 'Corn', 1],
    [3, 'Tomato', 2],
    [4, 'Eggplant', 2],
    [5, 'Banana', 3],
    [6, 'Mango', 3],
    [7, 'Peanut', 4],
    [8, 'Cassava', 5],
    [9, 'Sweet Potato', 5],
    [10, 'Watermelon', 3],
];
$stmt = $pdo->prepare(
    'INSERT INTO species (species_id,crop_name,crop_category_id) VALUES (?,?,?)',
);
foreach ($species as $s) {
    $stmt->execute($s);
}

// crop_id, species_id, variety, growing_period_days, season_id
$crops = [
    [1, 1, 'NSIC Rc 222', 115, 1],
    [2, 2, 'Yellow Corn', 100, 2],
    [3, 3, 'Diamante Max', 90, 2],
    [4, 4, 'Morena', 100, 3],
    [5, 5, 'Lakatan', 300, 3],
    [6, 6, 'Carabao', 150, 3],
    [7, 7, 'BPI Pn 9', 110, 3],
    [8, 8, 'Golden Yellow', 240, 3],
    [9, 9, 'NSIC Sp 30', 120, 3],
    [10, 10, 'Sweet 16', 85, 2],
];
$stmt = $pdo->prepare(
    'INSERT INTO crop (crop_id,species_id,variety,growing_period_days,season_id) VALUES (?,?,?,?,?)',
);
foreach ($crops as $c) {
    $stmt->execute($c);
}

$farmers = [
    [1, 'Juan', 'Dela Cruz', '09171234567', 'Cebu City', '2026-10-26'],
    [2, 'Maria', 'Santos', '09181234567', 'Carcar City, Cebu', '2026-10-26'],
    [3, 'Pedro', 'Reyes', '09191234567', 'Danao City, Cebu', '2026-10-26'],
    [4, 'Ana', 'Garcia', '09201234567', 'Argao, Cebu', '2026-10-26'],
    [5, 'Jose', 'Mendoza', '09211234567', 'Bogo City, Cebu', '2026-10-26'],
    [6, 'Ramon', 'Flores', '09221234567', 'Toledo City, Cebu', '2026-10-26'],
    [7, 'Elena', 'Cruz', '09231234567', 'Naga City, Cebu', '2026-10-26'],
    [8, 'Antonio', 'Lim', '09241234567', 'Minglanilla, Cebu', '2026-10-26'],
    [9, 'Rosa', 'Fernandez', '09251234567', 'Liloan, Cebu', '2026-10-26'],
    [10, 'Mario', 'Villanueva', '09261234567', 'Compostela, Cebu', '2026-10-26'],
    [11, 'Teresa', 'Navarro', '09271234567', 'Talisay City, Cebu', '2026-10-26'],
    [12, 'Carlos', 'Ramos', '09281234567', 'Consolacion, Cebu', '2026-10-26'],
    [13, 'Liza', 'Torres', '09291234567', 'Dalaguete, Cebu', '2026-10-26'],
    [14, 'Roberto', 'Castillo', '09301234567', 'Barili, Cebu', '2026-10-26'],
    [15, 'Susan', 'Aquino', '09311234567', 'Sibonga, Cebu', '2026-10-26'],
];
$stmt = $pdo->prepare(
    'INSERT INTO farmer (farmer_id,first_name,last_name,contact_number,address,date_registered) VALUES (?,?,?,?,?,?)',
);
foreach ($farmers as $f) {
    $stmt->execute($f);
}

// farm_id, farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id
$farms = [
    [1, 1, 'Dela Cruz Farm', 'Cebu City', 2.5, 1, 1],
    [2, 2, 'Santos Rice Farm', 'Carcar City, Cebu', 4.2, 2, 2],
    [3, 3, 'Reyes Agricultural Farm', 'Danao City, Cebu', 3.8, 3, 1],
    [4, 4, 'Garcia Vegetable Farm', 'Argao, Cebu', 1.75, 1, 3],
    [5, 5, 'Mendoza Farm', 'Bogo City, Cebu', 5.0, 4, 2],
    [6, 6, 'Flores Corn Farm', 'Toledo City, Cebu', 3.4, 3, 1],
    [7, 7, 'Cruz Family Farm', 'Naga City, Cebu', 2.8, 1, 2],
    [8, 8, 'Lim Agricultural Farm', 'Minglanilla, Cebu', 2.25, 2, 3],
    [9, 9, 'Fernandez Farm', 'Liloan, Cebu', 3.1, 3, 1],
    [10, 10, 'Villanueva Green Farm', 'Compostela, Cebu', 4.5, 1, 2],
    [11, 11, 'Navarro Rice Field', 'Talisay City, Cebu', 3.75, 4, 2],
    [12, 12, 'Ramos Farm', 'Consolacion, Cebu', 2.6, 1, 1],
    [13, 13, 'Torres Vegetable Farm', 'Dalaguete, Cebu', 1.8, 3, 3],
    [14, 14, 'Castillo Agricultural Farm', 'Barili, Cebu', 5.5, 2, 2],
    [15, 15, 'Aquino Farm', 'Sibonga, Cebu', 3.2, 1, 1],
];
$stmt = $pdo->prepare(
    'INSERT INTO farm (farm_id,farmer_id,farm_name,location,area_hectares,soil_type_id,irrigation_type_id) VALUES (?,?,?,?,?,?,?)',
);
foreach ($farms as $f) {
    $stmt->execute($f);
}

// planting_id, farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, planting_status_id
$plantings = [
    [1, 1, 1, '2026-01-15', 1.0, 30.0, '2026-05-10', 2],
    [2, 2, 2, '2026-01-20', 1.2, 35.0, '2026-04-29', 2],
    [3, 3, 3, '2026-01-25', 0.8, 25.0, '2026-04-24', 2],
    [4, 4, 4, '2026-02-01', 0.9, 28.0, '2026-05-11', 2],
    [5, 5, 5, '2026-02-05', 1.5, 50.0, '2026-12-01', 2],
    [6, 6, 1, '2026-02-10', 0.6, 20.0, '2026-06-05', 2],
    [7, 7, 2, '2026-02-15', 1.1, 32.0, '2026-05-25', 2],
    [8, 8, 3, '2026-02-20', 0.7, 22.0, '2026-05-20', 2],
    [9, 9, 4, '2026-02-25', 0.85, 26.0, '2026-06-04', 2],
    [10, 10, 6, '2026-03-01', 1.3, 40.0, '2026-07-29', 2],
    [11, 11, 1, '2026-02-09', 0.5, 20.0, '2026-06-04', 2],
    [12, 12, 2, '2026-02-12', 0.6, 25.0, '2026-05-23', 2],
    [13, 13, 3, '2026-02-15', 0.7, 30.0, '2026-05-16', 2],
    [14, 14, 4, '2026-02-18', 0.8, 35.0, '2026-05-29', 2],
    [15, 15, 5, '2026-02-21', 0.9, 40.0, '2026-12-18', 2],
    [16, 1, 6, '2026-02-24', 1.0, 45.0, '2026-07-24', 2],
    [17, 2, 7, '2026-02-27', 1.1, 10.0, '2026-06-17', 2],
    [18, 3, 8, '2026-03-02', 1.2, 15.0, '2026-10-28', 2],
    [19, 4, 9, '2026-03-05', 1.3, 20.0, '2026-07-03', 2],
    [20, 5, 10, '2026-03-08', 1.4, 25.0, '2026-06-01', 2],
    [21, 6, 1, '2026-03-11', 0.5, 30.0, '2026-07-04', 1],
    [22, 7, 2, '2026-03-14', 0.6, 35.0, '2026-06-22', 1],
    [23, 8, 3, '2026-03-17', 0.7, 40.0, '2026-06-15', 1],
    [24, 9, 4, '2026-03-20', 0.8, 45.0, '2026-06-28', 1],
    [25, 10, 5, '2026-03-23', 0.9, 10.0, '2027-01-17', 1],
    [26, 11, 6, '2026-03-26', 1.0, 15.0, '2026-08-23', 1],
    [27, 12, 7, '2026-03-29', 1.1, 20.0, '2026-07-17', 1],
    [28, 13, 8, '2026-04-01', 1.2, 25.0, '2026-11-27', 1],
];
$stmt = $pdo->prepare(
    'INSERT INTO planting (planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,planting_status_id) VALUES (?,?,?,?,?,?,?,?)',
);
foreach ($plantings as $p) {
    $stmt->execute($p);
}

// harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remark_id
$harvests = [
    [1, 1, '2026-05-07', 900.0, 22.0, 1, 1],
    [2, 2, '2026-04-25', 1025.0, 25.5, 2, 2],
    [3, 3, '2026-04-18', 1150.0, 29.0, 3, 2],
    [4, 4, '2026-05-01', 1275.0, 32.5, 1, 1],
    [5, 5, '2026-11-20', 1400.0, 36.0, 2, 2],
    [6, 6, '2026-06-26', 1525.0, 39.5, 3, 2],
    [7, 7, '2026-05-20', 1650.0, 43.0, 1, 1],
    [8, 8, '2026-09-30', 1775.0, 46.5, 2, 2],
    [9, 9, '2026-06-05', 1900.0, 22.0, 3, 2],
    [10, 10, '2026-05-04', 2025.0, 25.5, 1, 1],
    [11, 11, '2026-06-06', 2150.0, 29.0, 2, 2],
    [12, 12, '2026-05-25', 2275.0, 32.5, 3, 2],
    [13, 13, '2026-05-18', 2400.0, 36.0, 1, 1],
    [14, 14, '2026-05-31', 2525.0, 39.5, 2, 2],
    [15, 15, '2026-12-20', 2650.0, 43.0, 3, 2],
    [16, 16, '2026-07-26', 2775.0, 46.5, 1, 1],
    [17, 17, '2026-06-19', 2900.0, 22.0, 2, 2],
    [18, 18, '2026-10-30', 3025.0, 25.5, 3, 2],
    [19, 19, '2026-07-05', 3150.0, 29.0, 1, 1],
    [20, 20, '2026-06-03', 3275.0, 32.5, 2, 2],
];
$stmt = $pdo->prepare(
    'INSERT INTO harvest (harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_grade_id,remark_id) VALUES (?,?,?,?,?,?,?)',
);
foreach ($harvests as $h) {
    $stmt->execute($h);
}

// Fix autoincrement sequences
$pdo->exec("UPDATE sqlite_sequence SET seq = 10 WHERE name = 'species'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 10 WHERE name = 'crop'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 15 WHERE name = 'farmer'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 15 WHERE name = 'farm'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 28 WHERE name = 'planting'");
$pdo->exec("UPDATE sqlite_sequence SET seq = 20 WHERE name = 'harvest'");

echo "SQLite database created at: $dbPath\n";
echo 'Tables: ' .
    implode(
        ', ',
        $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN),
    ) .
    "\n";
foreach ([
    'crop_category',
    'season',
    'soil_type',
    'irrigation_type',
    'planting_status',
    'quality_grade',
    'species',
    'crop',
    'farmer',
    'farm',
    'planting',
    'harvest',
    'harvest_remarks',
] as $t) {
    echo "$t: " . $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn() . " rows\n";
}
