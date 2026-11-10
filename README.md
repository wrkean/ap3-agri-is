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

## Setup

### 1. Supabase

Follow the detailed instructions in `~/Documents/supabase-setup-ap3.md` to:
- Create a Supabase project
- Run the schema SQL (tables, constraints, lookup data, sample data)
- Get your Project URL and anon key

### 2. Environment

```bash
cp .env.example .env
# Edit .env and paste your Supabase URL and anon key
```

### 3. Run the app

```bash
# From the project root
php -S localhost:8000
```

Then open http://localhost:8000 in your browser.

## Credentials (demo)

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@example.com | admin123 |
| Staff | staff@example.com | staff123 |

## Conventional Commits

This project uses conventional commit messages (`feat:`, `chore:`, `docs:`, `fix:`, etc.) as required by the class.

## Deliverables

- Working source code (this repo)
- Supabase PostgreSQL database with tables, constraints, relationships, lookup data
- ERD and normalized design (UNF → 1NF → 2NF → 3NF) — see `docs/`
- Project documentation — see `docs/`
- Screenshots of working modules
- Test results
