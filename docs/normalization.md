# Normalization — Agriculture Information System

## Unnormalized Form (UNF)

A single table storing all data, with repeating groups:

```
FARMER_RECORD (farmer_id, first_name, last_name, contact_number, address, date_registered,
  farm_name, location, area_hectares, soil_type, irrigation_type,
  crop_name, variety, crop_category, growing_period_days, recommended_season,
  planting_date, area_planted, seed_quantity, expected_harvest_date, status,
  harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remarks)
```

**Problems:** repeating groups (one farmer has many farms; one farm has many plantings; one planting has many harvests), multivalued attributes, redundant data.

## First Normal Form (1NF)

- Remove repeating groups; each cell contains a single atomic value.
- Split into separate tables: FARMER, FARM, CROP, PLANTING, HARVEST.

```
FARMER (farmer_id, first_name, last_name, contact_number, address, date_registered)
FARM (farm_id, farmer_id, farm_name, location, area_hectares, soil_type, irrigation_type)
CROP (crop_id, crop_name, variety, crop_category, growing_period_days, recommended_season)
PLANTING (planting_id, farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status)
HARVEST (harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remarks)
```

## Second Normal Form (2NF)

- All non-key attributes fully depend on the primary key (no partial dependencies).
- Each table has a single-column PK (IDENTITY), so partial dependencies cannot occur. No change from 1NF.

## Third Normal Form (3NF)

- Remove transitive dependencies (non-key attributes depending on other non-key attributes).
- `crop_name`, `variety`, etc. depend on `crop_id`, not on `farm_id` or `planting_id`. All non-key attributes depend only on the PK.
- Lookup table for harvest remarks (Harvest Remarks) added to resolve controlled values.

```
HARVEST_REMARKS (remark_id, remark_description)
HARVEST (harvest_id, planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remark_id, remarks)
```

## Final 3NF Tables

- FARMER, FARM, CROP, PLANTING, HARVEST, HARVEST_REMARKS

## Relationships

- FARMER 1:M FARM
- FARM 1:M PLANTING
- CROP 1:M PLANTING
- PLANTING 1:M HARVEST
- HARVEST_REMARKS 1:M HARVEST (lookup, optional)