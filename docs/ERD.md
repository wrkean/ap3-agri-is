# Entity-Relationship Diagram (ERD)

## Tables and Relationships

### FARMER
- **farmer_id** (PK, IDENTITY)
- first_name, last_name, contact_number, address, date_registered, created_at, updated_at

### FARM
- **farm_id** (PK, IDENTITY)
- **farmer_id** (FK → FARMER.farmer_id)
- farm_name, location, area_hectares, soil_type, irrigation_type, created_at, updated_at

### CROP
- **crop_id** (PK, IDENTITY)
- crop_name, variety, crop_category, growing_period_days, recommended_season

### PLANTING
- **planting_id** (PK, IDENTITY)
- **farm_id** (FK → FARM.farm_id)
- **crop_id** (FK → CROP.crop_id)
- planting_date, area_planted, seed_quantity, expected_harvest_date, status, created_at, updated_at

### HARVEST
- **harvest_id** (PK, IDENTITY)
- **planting_id** (FK → PLANTING.planting_id)
- harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remark_id (FK → HARVEST_REMARKS.remark_id), remarks, created_at, updated_at

### HARVEST_REMARKS (lookup)
- **remark_id** (PK)
- remark_description

## Relationships Summary

```
FARMER (1) ──< FARM (M)
FARM   (1) ──< PLANTING (M)
CROP   (1) ──< PLANTING (M)
PLANTING (1) ──< HARVEST (M)
HARVEST_REMARKS (1) ──< HARVEST (M) [optional]
```

## Notes

- All primary keys are integer IDENTITY columns (auto-increment).
- Foreign keys enforce referential integrity.
- `created_at` and `updated_at` audit fields are included on FARMER, FARM, PLANTING, and HARVEST.
- RLS is enabled on all tables; permissive policies are provided for the school demo. Tighten for production.