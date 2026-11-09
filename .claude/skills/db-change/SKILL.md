---
name: db-change
description: Change the database schema with a new migration, and update the model, enums, factory, seeder, and docs/engineering/database.md in the same change.
argument-hint: "[description of the schema change]"
disable-model-invocation: true
---

# Database change

**Role: Schema engineer.** Choose a migration and backfill that work on existing PostGIS data and preserve the authorized contract. Escalate destructive changes or unresolved visibility decisions. Hand off forward and rollback evidence plus any data prerequisites.

The change to make: $ARGUMENTS

`docs/engineering/database.md` is the schema of record. The migration and that document change together, or the next session builds on a wrong picture.

## Read first

1. `docs/engineering/database.md`: "Schema rules", "Visibility classes", the tables involved, "Seeder requirements".
2. `docs/engineering/privacy-rules.md`, "Field classes", if the change touches `businesses`, `business_years`, `wishes`, or `record_view_logs`.
3. `database/migrations/`: what already exists.

## Steps

1. **Branch.** If on `main` or `staging`, create a work branch inside `staging`: `feat/<id>-<short-name>` or `chore/<short-name>`.
2. **Restate the change** as tables and columns with types, keys, and indexes. If it differs from `database.md`, say exactly how and ask before going ahead. A schema that drifts from the doc is how privacy fields leak.
3. **Write a new migration:** `php artisan make:migration <name> --no-interaction`. Never edit a migration merged to `staging` (check with `git log origin/staging -- database/migrations/<file>`). One migration may create several related tables.
4. **Follow the schema rules:**
   - Not null unless "unknown" is a real state.
   - `constrained()` foreign keys, restrict on delete unless the child cannot exist alone.
   - Unique constraints for business rules. `enum()` for enum columns.
   - PSGC `string(10)`, money `decimal(16, 2)`, years `smallInteger`.
   - Geometry `geometry('boundary', subtype: 'multipolygon', srid: 4326)` or `point`, plus `spatialIndex()`.
   - An index for every foreign key and every frequent `where`, `join`, or `order by` column.
5. **Update everything that depends on it, together:**
   - the model: `#[Fillable]`, `#[Hidden]` (the sales column and every never-displayed field), `casts()` with enums and `'decimal:2'`, relationships with return types
   - one backed enum per enum column in `app/Enums/`
   - the factory, with states the tests will need
   - the seeder, if the table is seeded
6. **Update `docs/engineering/database.md`** so it matches the migration exactly.
7. **Prove it:** `composer db:reset`, then `php artisan test --compact`.
8. **Say which summary tables are affected** (`stats_area_year`, `stats_type_cohort`, `stats_outlook`) and which metrics may need a `/metric-builder` follow-up.

## Done when

A new migration exists, the model, enums, factory, and seeder match it, `database.md` matches it, `composer db:reset` and the tests pass, and the person knows which summary tables are affected. Then suggest `/commit`.
