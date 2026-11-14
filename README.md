# AP3 — Agriculture Information System

**Cebu Technological University — Cebu City**  
**Database:** Supabase PostgreSQL  |  **Application:** PHP + Tailwind CSS

A web-based Agriculture Information System demonstrating database normalization,
relational design, secure data management, CRUD operations, reporting, and
dashboard visualization.

## Modules

- Farmers
- Farms
- Crops
- Planting Records
- Harvest Records
- Dashboard (summary cards + charts)
- Reports (farmers & farms, crops per farm, plantings by status, harvests, harvested quantity, estimated value)
- Authentication (Administrator / Staff roles)

## Database design

The schema is normalized to 3NF and uses lookup tables for every controlled
value, so dropdowns display a descriptive label while storing the lookup ID:

| Lookup table | Used by | Example values |
|---|---|---|
| `crop_category` | `species` | Cereal, Vegetable, Fruit, Legume, Root Crop |
| `season` | `crop` | Wet Season, Dry Season, Year Round |
| `soil_type` | `farm` | Loam, Clay Loam, Sandy Loam, Clay |
| `irrigation_type` | `farm` | Rainfed, Irrigated, Drip Irrigation |
| `planting_status` | `planting` | Growing, Harvested, Failed |
| `quality_grade` | `harvest` | Grade A, Grade B, Grade C |
| `harvest_remarks` | `harvest` | Good Harvest, Acceptable Quality, Low Yield, Poor Quality, Damaged Harvest |

Crop names live in `species` (with their category) and plantable varieties live
in `crop` (with their growing period and recommended season), which removes the
`variety → crop_name → crop_category` dependency.

- Entity-relationship diagram: [`docs/ERD.md`](docs/ERD.md) ([image](docs/erd.png), [vector](docs/erd.svg))
- Normalization walkthrough (UNF → 1NF → 2NF → 3NF): [`docs/normalization.md`](docs/normalization.md)

## Setup

The app supports **two database backends** and auto-detects which to use:

### Option A: SQLite (no setup required — default)

A pre-built SQLite database is included at `database/agri.db`. Just run:

```bash
php -S localhost:8000
```

Then open http://localhost:8000 in your browser. Login with `admin@example.com` / `admin123`.

To rebuild the SQLite database from scratch:

```bash
php database/build_sqlite.php
```

### Option B: Supabase PostgreSQL

Follow the detailed instructions in `~/Documents/supabase-setup-ap3.md` to:
- Create a Supabase project
- Run `database/schema.sql` in the Supabase SQL Editor
- Get your Project URL and anon key

Then create your `.env` file:

```bash
cp .env.example .env
# Edit .env and paste your Supabase URL and anon key
```

The app will automatically use Supabase when `.env` is configured. Otherwise it falls back to SQLite.

## Credentials (demo)

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@example.com | admin123 |
| Staff | staff@example.com | staff123 |


## Tests

```bash
vendor/bin/phpunit            # 57 tests, 225 assertions
```

The suite covers the query layer (filters, `in` lists, joins, nested selects,
pagination, aggregates), CRUD workflows, the seeded lookup data, and the
authentication/role rules. Tests run against the SQLite backend.

## Deliverables

- Working source code (this repo)
- Supabase PostgreSQL database with tables, constraints, relationships, lookup data
- ERD and normalized design (UNF → 1NF → 2NF → 3NF) — see `docs/`
- Project documentation — see `docs/`
- Screenshots of working modules
- Test results
