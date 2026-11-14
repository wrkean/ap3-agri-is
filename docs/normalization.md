# Normalization — Agriculture Information System

## Unnormalized Form (UNF)

A single record stores everything about a farmer, their farms, plantings and
harvests, with repeating groups for farms, plantings and harvests:

```
FARMER_RECORD (farmer_id, first_name, last_name, contact_number, address, date_registered,
  farm_name, location, area_hectares, soil_type, irrigation_type,
  crop_name, variety, crop_category, growing_period_days, recommended_season,
  planting_date, area_planted, seed_quantity, expected_harvest_date, status,
  harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remarks)
```

**Problems**

- Repeating groups: one farmer has many farms; one farm has many plantings; one
  planting has many harvests.
- Redundant data: the farmer's name and address are repeated on every farm,
  planting and harvest row.
- Update, insert and delete anomalies. Changing a farmer's contact number means
  updating many rows; a farmer with no harvest cannot be recorded without
  inventing harvest values; deleting the last harvest of a farm erases the
  farmer.
- Controlled values (soil type, irrigation type, category, season, status,
  grade, remarks) are typed as free text, so the same value can be spelled
  differently in different rows.

## First Normal Form (1NF)

**Rule:** every column holds a single atomic value and there are no repeating
groups.

- Split the record into one table per entity: `FARMER`, `FARM`, `CROP`,
  `PLANTING`, `HARVEST`.
- Give each table a single-column primary key so each row is uniquely
  identified.
- Move the farm, planting and harvest details into their own rows instead of
  repeating columns inside the farmer record.

```
FARMER   (farmer_id, first_name, last_name, contact_number, address, date_registered)
FARM     (farm_id, farmer_id, farm_name, location, area_hectares, soil_type, irrigation_type)
CROP     (crop_id, crop_name, variety, crop_category, growing_period_days, recommended_season)
PLANTING (planting_id, farm_id, crop_id, planting_date, area_planted, seed_quantity,
          expected_harvest_date, status)
HARVEST  (harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg,
          quality_grade, remarks)
```

All values are now atomic. Controlled values are still plain text at this
stage, so the anomaly from inconsistent spelling remains.

## Second Normal Form (2NF)

**Rule:** every non-key attribute must depend on the whole primary key — no
partial dependencies.

- Each table in 1NF has a single-column surrogate primary key, so no non-key
  attribute can depend on only part of the key. No table is split at this
  stage.
- The natural keys matter for review. `CROP` has the natural candidate key
  `(crop_name, variety)` because a variety belongs to exactly one crop name.
  Against that key, `crop_category` and `recommended_season` depend on
  `crop_name` alone, which is a partial dependency. The design keeps surrogate
  keys, so the same problem appears in 3NF as a transitive dependency, and it
  is removed there.

## Third Normal Form (3NF)

**Rule:** no non-key attribute may depend on another non-key attribute
(no transitive dependencies).

The following dependencies were removed:

| Dependency removed | Why it is wrong | Resolution |
|---|---|---|
| `crop_id → variety → crop_name → crop_category` | The crop name and category are facts about the species, not about one variety. Changing a category would mean updating every variety of that crop. | Split `CROP` into `SPECIES (crop_name, crop_category_id)` and `CROP (species_id, variety, growing_period_days, season_id)`. |
| `farm_id → soil_type` / `irrigation_type` | A soil or irrigation label is a controlled value repeated on many farms; a typo creates a second, inconsistent value. | `SOIL_TYPE` and `IRRIGATION_TYPE` lookup tables referenced by ID. |
| `crop_id → crop_category`, `crop_id → recommended_season` | Category and season are reusable domain values, not free text. | `CROP_CATEGORY` and `SEASON` lookup tables referenced by ID. |
| `planting_id → status` | Status is one of a small fixed set and is repeated across plantings. | `PLANTING_STATUS` lookup table referenced by ID. |
| `harvest_id → quality_grade` | Grade is a controlled value repeated across harvests. | `QUALITY_GRADE` lookup table referenced by ID. |
| `harvest.remarks` duplicating `remark_id` | The same remark was stored twice in one row. The seed data already disagreed: remark 1 is *Good Harvest* while the text said *High quality harvest*. | Drop the free-text `remarks` column; keep `remark_id` as the single source of truth. |

Each remaining non-key attribute depends only on the primary key, the whole
primary key, and nothing but the key.

## Final 3NF Tables

```
CROP_CATEGORY   (crop_category_id, category_name)
SEASON          (season_id, season_name)
SOIL_TYPE       (soil_type_id, type_name)
IRRIGATION_TYPE (irrigation_type_id, type_name)
PLANTING_STATUS (planting_status_id, status_name)
QUALITY_GRADE   (quality_grade_id, grade_name)
HARVEST_REMARKS (remark_id, remark_description)

SPECIES  (species_id, crop_name, crop_category_id)
CROP     (crop_id, species_id, variety, growing_period_days, season_id)

FARMER   (farmer_id, first_name, last_name, contact_number, address,
          date_registered, created_at, updated_at)
FARM     (farm_id, farmer_id, farm_name, location, area_hectares,
          soil_type_id, irrigation_type_id, created_at, updated_at)
PLANTING (planting_id, farm_id, crop_id, planting_date, area_planted,
          seed_quantity, expected_harvest_date, planting_status_id,
          created_at, updated_at)
HARVEST  (harvest_id, planting_id, harvest_date, quantity_kg,
          selling_price_per_kg, quality_grade_id, remark_id,
          created_at, updated_at)
```

## Fourth and Fifth Normal Form

- **4NF:** after `FARM` and `PLANTING` were separated, no table contains two
  independent multivalued facts about the same key, so there are no
  multivalued dependencies to remove.
- **5NF:** no table can be losslessly decomposed into smaller tables without
  losing a constraint, so no join dependency remains.
- The design is therefore in 3NF, BCNF, 4NF and 5NF; no further decomposition
  is warranted.
