# Database

The schema of record, and the rules for changing it. A good schema keeps the data correct on its own, makes the metrics simple SQL, and makes privacy enforceable. Read this before any migration, model, factory, or seeder.

Field names follow the city's business permit application, so real records can replace simulated ones without a rebuild.

Change the schema only with a new migration plus an edit to this file, in the same pull request. Use the `/db-change` skill.

## Engine

PostgreSQL 17 with PostGIS 3.5, in Docker (`compose.yaml`). Two databases: `cagayan_business` for development and `cagayan_business_test` for tests. Tests never use SQLite, because spatial queries and the metric SQL must be tested for real.

## Schema rules

### Let the database enforce the rules

- **Not null by default.** A column is nullable only when "unknown" is a real state, and this file says why.
- **Foreign keys on every reference**, with `constrained()`. Default `restrictOnDelete()`. Use `cascadeOnDelete()` only for rows that cannot exist without their parent (`business_years` under `businesses`).
- **Unique constraints mirror business rules.** One row per business per year is `unique(['business_id', 'year'])`, not a check in PHP.
- **Enum columns** use `$table->enum()`. On PostgreSQL that is a string column with a CHECK constraint, so bad values cannot get in from a seeder or raw SQL. The model casts it to a backed PHP enum in `app/Enums/`.
- **Insert-only tables** (`record_view_logs`) have no `updated_at` and are never updated or deleted by app code.

### Types

| Data | Column | PHP |
| :- | :- | :- |
| PSGC code | `string(10)`, never an integer (codes can start with zero) | `string` |
| Money | `decimal(16, 2)`, sums in `decimal(18, 2)` | `string` via `'decimal:2'` cast, never `float` |
| Year | `smallInteger` | `int` |
| Enum | `enum()` (string with CHECK) | backed enum |
| Area shape | `geometry('boundary', subtype: 'multipolygon', srid: 4326)` | string (WKB) or GeoJSON from SQL |
| Point | `geometry('location', subtype: 'point', srid: 4326)` | same |
| Timestamps | `timestamps()` on every table unless noted | `CarbonImmutable` |

- Geometry is always SRID 4326 (longitude, latitude). Cast to `geography` for any area or distance in meters.
- Loaded shapes are wrapped with `ST_Multi`, since some features are plain polygons.
- No soft deletes in the MVP.

### Indexes

An index exists for every foreign key, every column used in a frequent `where`, `join`, or `order by`, and every geometry column (GiST, via `spatialIndex()`). Add an index in the same migration as the query that needs it.

Do not index everything. Each index slows writes and takes space. At about 4,000 businesses, a plain btree is enough for name search. Add `pg_trgm` only if search gets slow.

### Migrations

- Create with `php artisan make:migration <name> --no-interaction`.
- **Never edit a migration that has been merged to `staging`.** Teammates have already run it. Add a new one.
- One migration may create several related tables.
- `down()` reverses `up()`. If it cannot (data would be lost), say so in a one-line comment.
- After a schema change: `composer db:reset`, then `composer ci:check`.

### Related files change together

A schema change also updates, in the same pull request:

1. the model (`#[Fillable]`, `#[Hidden]`, `casts()`, relationships)
2. the enum in `app/Enums/`
3. the factory, with states for the cases tests need
4. the seeder, if the table is seeded
5. this file

## Visibility classes

Every field on a business belongs to exactly one class. The class decides which Data object may carry it. See `privacy-rules.md`.

- **Public:** anyone may see it.
- **Admin:** logged-in Capitol administrators, and Staff assigned to the business's town (D40).
- **Never displayed:** stored and used in calculations, never shown for a single business to anyone. Always in the model's `#[Hidden]`.

## Tables

### municipalities

The cities and municipalities of Cagayan. All appear on the province map. Only connected ones have business data.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `psgc_code` | string(10) | unique |
| `name` | string | |
| `kind` | enum `city`, `municipality` | |
| `is_connected` | boolean, default false | True for Tuguegarao City only in the MVP. Others are drawn grey as "Not yet connected". |
| `boundary` | geometry(MultiPolygon, 4326), nullable | Nullable until loaded. Spatial index. |

`MunicipalitySeeder` reads codes, names, and kinds from `data/boundaries/cagayan-municipalities.geojson`. The shapes are loaded with roadmap F2.

### town_facts

Real public figures per town and year: population, land area, barangays, income class, and poverty incidence. Government revenue and expenses moved to the finance ledger below (D35). Loaded from `data/towns/town-facts.csv` by `php artisan towns:import`, which replaces the whole table with the file. Read by `TownAtlasQuery` (metrics.md, "Town figures"). Public, so no visibility class applies.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `municipality_id` | foreign key | cascade on delete |
| `measure` | enum `population`, `land_area_km2`, `barangay_count`, `income_class`, `poverty_incidence` | `App\Enums\TownMeasure` |
| `year` | smallint | The year the figure is for |
| `value` | decimal(16, 2) | Residents, km², percent, or the class number |
| `source_url` | string(500) | Required |
| `checked_on` | date, nullable | When a team member checked it against the official release |

Unique `(municipality_id, measure, year)`, which also serves the lookups. A new census year is new rows, never a schema change.

### Finance ledger

Government finance figures from COA reports (D35, metrics.md M19 to M27). The tables are loaded by `php artisan finances:import` (`composer data:finances`) from `data/towns/finances/`, which the Python parser in `tools/coa/` writes (D34). `php artisan finances:export` saves sign-offs and manual figures back to those CSVs, so `composer db:reset` rebuilds the same verified ledger. The import never deletes manual figures. A parsed figure whose value changes goes back to pending. Workbook figures load only for the towns in `cagayan.finances.workbook_towns`; every Executive Summary loads. Public figures, so no visibility class applies, but only `verified` figures are published.

#### finance_lines

The line catalog, one row per `data/towns/finances/lines.csv` row.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `code` | string(80) | unique, such as `performance.revenue` |
| `parent_code` | string(80), nullable | The total a breakdown line belongs to |
| `label` | string(120) | Display label, such as "Government Revenue" |
| `group` | string(40) | Field Catalog section: `totals`, `budget`, `revenue_sources`, `business_taxes`, `development`, `checks` (read for arithmetic only, never stored), or a derived group: `budget_share`, `revenue_share`, `development_share`, `per_capita` |
| `unit` | string(10) | `php` or `percent` |
| `displayed` | boolean | False for lines kept only for the arithmetic checks |
| `source` | string(40) | `executive_summary`, `fs_workbook`, `annex_c1`, `fs_scbaa`, or `derived` (computed in `stats_finance_year`, never entered or imported) |
| `sort` | smallint | File order |

#### finance_documents

A COA report for one town and fiscal year, or the record that COA had not published it on the date checked.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `municipality_id` | foreign key | restrict on delete |
| `fiscal_year` | smallint | The report year |
| `kind` | enum `executive_summary`, `fs_workbook`, `annex_workbook` | `App\Enums\FinanceDocumentKind` |
| `availability` | enum `available`, `not_published` | `App\Enums\FinanceAvailability`. Not published shows as Not available, never zero |
| `file` | string(300), nullable | Path inside `data/towns/` |
| `source_url` | string(500), nullable | The exact COA download |
| `sha256` | char(64), nullable | Checksum of the file as downloaded |
| `checked_on` | date | When the file or its absence was checked |

Unique `(municipality_id, fiscal_year, kind)`. A document read from a Capitol upload has `file` under `finance-uploads/` on the private disk and stays out of the CSV import and export (D42).

#### finance_uploads

A COA report uploaded on Capitol Finances (D42).

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `public_id` | uuid | unique |
| `municipality_id` | foreign key | restrict on delete |
| `fiscal_year` | smallint | The report year |
| `kind` | string(20) | A key of `cagayan.finances.uploads.kinds`: `executive_summary`, `fs_workbook`, `annex_workbook` or `archive` |
| `path` | string(255) | On the private `local` disk under `finance-uploads/` |
| `original_name` | string(255) | As uploaded |
| `sha256`, `size` | char(64), bigint | Checksum and bytes |
| `status` | string(10) | `App\Enums\FinanceUploadStatus`: queued, running, done, failed |
| `figures` | int | Figures left waiting for sign-off |
| `issues` | jsonb, nullable | Parser errors and warnings |
| `message` | string(500), nullable | A short outcome for the page; never an exception |
| `uploaded_by` | foreign key to `users`, nullable | Null on delete |

Unique `(municipality_id, fiscal_year, kind, sha256)`, so the same file is read once.

#### finance_figures

One figure, parsed from a report or entered by hand.

Manual-entry validation is centralized in `config/cagayan.php` under `finances` and enforced server-side. The browser receives the same year, amount, source-length, note-length and review-batch limits. New form entries require a displayed catalog line, a supported fiscal year no later than the selected report, a non-negative amount with at most two decimal places within `value_max`, a matching basis from the line's catalog group, and a plain-text note up to `note_max`. Only Budget and Actual Amounts permits Final Budget. These 13 stored lines do not include the derived surplus/deficit, so negative entries are rejected without changing M19.

Sources must be an existing report for the selected town/report year or an HTTPS URL whose actual host is `coa.gov.ph` or a subdomain. Credentials, alternate ports and lookalike domains are rejected. `OfficialCoaUrl` is shared by the form and CSV import. Manual CSV replay enforces the same amount, year, basis, source and text-length limits; historical empty notes can still round-trip. Structural database checks continue to enforce references, source presence and the unique verified observation. This UI correction changes no schema and does not certify a source's contents automatically.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `public_id` | uuid | unique. The identifier in URLs and exports |
| `municipality_id` | foreign key | restrict on delete |
| `fiscal_year` | smallint | The year the figure describes. A 2024 report also gives 2023 |
| `line_code` | string(80) | foreign key to `finance_lines.code` |
| `basis` | enum `actual`, `budget_final` | `App\Enums\FinanceBasis` |
| `value` | decimal(18, 2) | Pesos with cents; negative for a deficit |
| `definition_variant` | string(60), nullable | Set when the source label states another definition. M26 does not compute change across a variant |
| `finance_document_id` | foreign key, nullable | The report read. Required for parsed figures |
| `source_url` | string(500), nullable | For a manual figure without a document |
| `source_label` | string, nullable | The label as printed, for reviewers |
| `location` | string(255), nullable | The page, or every contributing cell such as `11 - SCBAA!K61; 11 - SCBAA!K62`. Limit in `cagayan.finances.location_max` (26-11-03) |
| `inputs` | jsonb, nullable | Each contributing cell and its amount, `[{location, value}]`, so a summed figure can be checked against the workbook (26-11-03) |
| `entry_method` | enum `parsed`, `manual` | `App\Enums\FinanceEntryMethod` |
| `extractor_version` | string(10), nullable | Parser rules that produced a parsed figure |
| `status` | enum `pending`, `verified`, `rejected`, `superseded`, default `pending` | `App\Enums\FinanceFigureStatus` |
| `entered_by`, `reviewed_by` | foreign keys to `users`, nullable | Null on delete. Not exported |
| `reviewed_on` | date, nullable | Required once the status is not pending |
| `note` | string(500), nullable | |

Rules the database enforces:

- At most one `verified` figure per `(municipality_id, fiscal_year, line_code, basis)`, by a partial unique index.
- One parsed figure per `(finance_document_id, line_code, fiscal_year, basis)`.
- Every figure has a document or a source URL.
- Parsed figures have a document.
- Reviewed figures have a date.

Indexes: `(fiscal_year, line_code)`, `(municipality_id, status)`, `line_code` and `finance_document_id`.

`App\Services\FinanceSignOff` applies the rest:

- When a later report restates a year, its figure supersedes the earlier one.
- A manual correction outranks every report.
- Nobody signs off a figure they entered.

### barangays

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `municipality_id` | foreign key | indexed |
| `psgc_code` | string(10) | unique |
| `name` | string | |
| `urban_rural` | enum `urban`, `rural`, nullable | From the PSA listing. This is the peer group. Null until entered (F2). |
| `population` | unsigned integer, nullable | From `data/barangays/tuguegarao-population.csv` (2024 census) |
| `population_year` | smallint, nullable | |
| `population_source` | string(500), nullable | The official release the figure comes from |
| `boundary` | geometry(MultiPolygon, 4326), nullable | Spatial index. Loaded with F2. |

Index `(municipality_id, urban_rural)` for peer groups. Tuguegarao City is PSGC `0201529000` with 49 barangays. `BarangaySeeder` reads codes and names from `data/boundaries/tuguegarao-barangays.geojson`, and `php artisan barangays:import` loads population from the CSV (`composer data:towns` runs it with `towns:import`). Shapes load by `psgc_code` with F2.

### business_types

The list a resident picks from and the level every breakdown uses. Friendly names on top, PSIC grouping underneath.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `code` | string | unique, a slug such as `pharmacy` |
| `name` | string | "Pharmacy" |
| `psic_section` | char(1) | PSIC section letter |
| `psic_section_name` | string | |
| `sort_order` | smallint | |

Aim for 25 to 35 types a resident would recognize: sari-sari store, grocery, pharmacy, hardware store, coffee shop, restaurant, bakery, salon or barbershop, repair shop, boarding house, bank or lending, clinic, tutorial center. Check each PSIC section against the PSA classification before seeding.

Business Type names are unique ignoring case (`business_types_name_unique` on `lower(name)`), at most `cagayan.inputs.business_type_name_max` characters when added on the Capitol page, and written in Title Case in the seeder (D38). Admins add and rename types there; `code` never changes. `cagayan.categories.max_types` caps the list because the page loads every type.

`BusinessTypeSeeder` holds 30 types, each under one starter Business Category (D39), including the Section A farms and fishponds. It runs `BusinessCategorySeeder` first, and re-running it moves existing types to their starter category. Its PSIC sections are not yet verified by the data member.

### business_categories

Admin-managed sectors such as Tourism, Agriculture, Fishery and Retail (D39). Every Business Type belongs to exactly one. Managed on the Capitol Business Categories page. `BusinessCategorySeeder` adds 13 starter categories, which Admins rename and recolor, and the data member reviews them. This table replaced `focus_tags` and its pivot `business_type_focus_tag` (D36, D38).

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `code` | string(40) | unique slug of the first name, never changes |
| `name` | string(60) | Limit in `cagayan.inputs.category_name_max`. Unique ignoring case (`business_categories_name_unique` on `lower(name)`) |
| `color` | smallint | Palette slot 1 to `cagayan.categories.colors` (the `--category-N` tokens), default 1. New categories take the least used slot |
| `sort_order` | smallint | |

`business_types.business_category_id` is a not null foreign key to it (restrict on delete, indexed), so a category total is the sum of its types and is never double counted. Industry (the PSIC section) stays on the type. A category crosses industries, so category sales are bands for everyone (P4).

### businesses

One row per registered business.

| Column | Type | Class | Notes |
| :- | :- | :- | :- |
| `id` | id | Public | |
| `public_id` | uuid | Public | unique; the identifier in URLs, as for every private record |
| `business_name` | string(55) | Public | |
| `business_type_id` | foreign key | Public | indexed |
| `description` | string(280) | Public | |
| `year_established` | smallint | Public | Year first registered |
| `barangay_id` | foreign key | Public | indexed |
| `location` | geometry(Point, 4326) | Public | Spatial index. Must fall inside the barangay; `BarangaySeeder` loads the barangay shapes the seeder places points in. |
| `owner_name` | string(80) | Admin | |
| `contact_number` | string(20) | Admin | The owner's number |
| `ownership_type` | enum `sole_proprietorship`, `partnership`, `corporation`, `cooperative` | Admin | |
| `size_category` | enum `micro`, `small`, `medium` | Admin | |
| `employee_count` | integer | Admin | |
| `is_simulated` | boolean, default true | System | |
| `source` | string, nullable | System | Where the record came from once real data arrives |
| `street_address` | string(160), nullable | Admin | Typed by hand; no geocoder (D40) |
| `business_phone` | string(20), nullable | Public | The business's own contact, published by choice (D41 format) |
| `business_email` | string(120), nullable | Public | |
| `is_storefront` | boolean, default true | Public | Home-based businesses show their barangay, never their pin |
| `registration_body` | enum `dti`, `sec`, `cda`, nullable | Admin | |
| `registration_number` | string(30), nullable | Admin | |
| `registration_valid_until` | date, nullable | Admin | DTI business names last five years |
| `tin` | string(17), nullable | Admin | From the BIR COR; shown masked to its last three digits |
| `rdo_code` | string(4), nullable | Admin | |
| `record_status` | enum `draft`, `submitted`, `returned`, `approved`, default `approved` | System | Only `approved` is public or counted (P15) |
| `submitted_by`, `reviewed_by` | foreign keys to users, nullable | System | null on delete |
| `submitted_at`, `reviewed_at` | timestamp, nullable | System | |
| `review_note` | string(280), nullable | Admin | Why a record was returned |
| `bir_verified_on` | date, nullable | Public | Set on approval when a BIR COR is on file; drives the BIR Registered badge |

### business_permits

One Mayor's permit per business per year (D40). Unique `(business_id, year)`.

| Column | Type | Class | Notes |
| :- | :- | :- | :- |
| `business_id` | foreign key | | cascade on delete |
| `permit_number` | string(30) | Admin | |
| `year` | smallint | Public | |
| `valid_from`, `valid_until` | date | Public | M32 Permit Status reads `valid_until` |

### business_documents

Uploaded files on the private `local` disk, served only through the authorized document route (D40). Admin class.

| Column | Type | Notes |
| :- | :- | :- |
| `public_id` | uuid | unique; used in the document URL |
| `business_id` | foreign key | cascade on delete, indexed |
| `kind` | enum | the Tuguegarao permit list plus `bir_cor` and `bir_seal_badge` (`App\Enums\DocumentKind`) |
| `path` | string(300) | never sent to a page |
| `original_name`, `mime`, `size`, `sha256` | | type and size limits in `cagayan.registry` |
| `issued_on`, `expires_on` | date, nullable | |
| `uploaded_by` | foreign key to users, nullable | null on delete |

### business_years

One row per business per year. Every trend, survival rate, and outlook reads this history.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `business_id` | foreign key, cascade on delete | |
| `year` | smallint | |
| `status` | enum `new`, `renewed`, `not_renewed`, `closed` | |
| `declared_gross_sales` | decimal(16, 2), nullable | **Never displayed.** Present only when status is `new` or `renewed` (a check constraint enforces it). In `#[Hidden]`. Read only by `database/sql/metrics/`. |

Unique `(business_id, year)`. Index `(year, status)`.

Status rules. The seeder follows them and a test checks them:

1. A business established in or after the first data year has its first row in `year_established`, status `new`. A business established earlier has its first row in the first data year, status `renewed`.
2. Each later row is `renewed`, `not_renewed`, or `closed`.
3. After `not_renewed` or `closed`, there are no further rows.
4. **Active in a year** means status `new` or `renewed`.
5. Every business active in one year has a row the next year, unless that year is past the latest data year.

Two checks follow, for every year after the first, each worth a test:

- Active this year = active last year + new - not renewed - closed.
- Renewed + not renewed + closed this year = active last year.

### wishes

What residents wish their town had. No resident name, contact, or raw network address.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `municipality_id` | foreign key | |
| `business_type_id` | foreign key, nullable | Optional when a specific business is named |
| `specific_business` | string(120), nullable | Requested company name, hidden from all users except the browser that sent it |
| `reason` | string(255), nullable | Shown only to the browser that wrote it |
| `submitter_hash` | string(64) | SHA-256 of the anonymous browser token in the `wish_submitter` cookie (decisions.md, D21). Never the token itself, never a network address. |
| `network_hash` | string(64), nullable | HMAC of the network address and the date, keyed with the app key. Changes daily, so it never links days. Null for seeded rows. Caps new respondents per network per day. |
| `created_at`, `updated_at` | timestamps | `updated_at` changes on an edit |

Index `(municipality_id, business_type_id)` for type counts, `(municipality_id, specific_business)` for named business counts, `(submitter_hash, created_at)` for the cooldown, and `(network_hash, created_at)` for the new respondent cap. The cooldown and edit checks run under a cache lock per `submitter_hash`, so two quick requests cannot both pass.

### wish_edits

One row per edit of a wish, so the weekly edit limit can be counted. Insert only.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `wish_id` | foreign key, cascade on delete | |
| `edited_at` | timestamp | no `created_at` or `updated_at` |

Index `(wish_id, edited_at)`.

### record_view_logs

Written every time an administrator opens a full business record. Insert only.

| Column | Type | Notes |
| :- | :- | :- |
| `id` | id | |
| `user_id` | foreign key | restrict on delete |
| `business_id` | foreign key | restrict on delete |
| `viewed_at` | timestamp | no `created_at` or `updated_at` |

Index `(business_id, viewed_at)`.

### users

Laravel's default `users` table (two-factor columns dropped, decisions.md O8), plus:

| Column | Type | Notes |
| :- | :- | :- |
| `role` | enum super_admin, admin, staff; nullable | Not fillable. Null denies workspace access. Existing capitol_admin accounts migrate to admin. |
| `public_id` | UUID, unique, required | Random external account identifier. Internal numeric keys never need to appear in URLs. |
| `is_active` | boolean, default true | Disabled accounts cannot sign in; existing sessions end on their next request. |
| `auth_version` | integer, default 1 | Incremented when role, active state, or password changes through the User model. Sessions bind to this version. |
| `birthday_month / birthday_day` | nullable small integers | Both absent or a valid month/day pair, allowing February 29. No birth year. Only the user's profile exposes these. |

### municipality_user

Composite primary key (user_id, municipality_id), constrained to users and municipalities. Staff may edit only assigned towns. Economic dashboard visibility remains province-wide. Assignments currently support authorization; their management UI comes later.

### activity_logs

UUID primary key, nullable actor_id (users FK, restrict deletion), event varchar(40), occurred_at timestamptz. Event and time indexed. A PostgreSQL trigger rejects UPDATE and DELETE. Stores safe security/account event codes only, never credentials, network addresses, birthdays, owner contacts, or business sales. Only Super Admin can read the stream. Console role assignments identify the affected account; the event explicitly identifies console origin, not an authenticated web actor.

### Input and future identity contracts

Display name is validated as 1–100 Unicode characters with outside whitespace trimmed, no markup or control/format characters, no automatic case conversion. The existing name column retains its storage size for legacy compatibility; new profile writes enforce the contract. Future business_name storage must be VARCHAR(55), matched by server and form limits. Formats for contact numbers and other future business fields must be settled with their forms, not inferred.

Keep local user IDs, roles, activation, assignments, display name, and birthday independent of authentication providers. A future identity link must use provider plus verified stable subject (and tenant where relevant), uniquely bound to the local account. Email is a sign-in/contact attribute, never a permanent provider identity or automatic role grant. No OAuth tables or dependencies are added in this foundation.


## Summary tables

Rebuilt by `php artisan metrics:refresh` from the SQL in `database/sql/metrics/`. Never edited by hand and never written by request code. Prefixed `stats_`.

### stats_area_year

The base grain. Every count and sales figure on screen rolls up from here. Built by `database/sql/metrics/10_stats_area_year.sql`. Industry and Business Category totals (M28 to M31) are summed from it by the Query, joining `business_types` and `business_categories`, rather than kept in a second table.

- `barangay_id`, `business_type_id`, `size_category`, `year`
- `active_count`, `new_count`, `renewed_count`, `not_renewed_count`, `closed_count`
- `declared_sales_total` decimal(18, 2)
- `declared_sales_max_single` decimal(16, 2). The largest single business's sales in the cell, for the dominance rule. When rolling up cells, take the maximum.
- unique `(barangay_id, business_type_id, size_category, year)`, index `(year, barangay_id)`

### stats_finance_year

Signed-off government finance series and their derived lines (metrics.md M19 to M27). Built by `database/sql/metrics/20_stats_finance_year.sql` from `verified` figures only. A missing figure has no row; the Query reports it as not published or not reported.

- `municipality_id`, nullable. Null rows are the Sum of Reporting Towns (M27), for peso lines only.
- `fiscal_year`, `metric` (a `finance_lines.code`), `value` decimal(20, 2), `definition_variant`
- `change` decimal(20, 2), `percent_change` decimal(10, 1), `comparable` boolean (M26)
- `census_year`, always null since per-resident lines were retired on 26-11-03 (M25); kept to avoid a destructive migration
- `restated_in`, the report year of a figure that replaced a different earlier figure (M26)
- `reporting_towns`, `total_towns`, `matched_towns`, for Sum of Reporting Towns rows

### stats_type_cohort

Survival by business type, city wide.

- `municipality_id`, `business_type_id`, `cohort_year`, `cohort_size`
- `survived_1y`, nullable when the following year has no data
- `survived_2y`, nullable when the second year has no data

### stats_outlook

One row per barangay per target year, including past target years for the backtest.

- `barangay_id`, `target_year`
- `businesses_low`, `businesses_mid`, `businesses_high`
- `sales_low`, `sales_mid`, `sales_high`
- `is_backtest` boolean. True when the target year already has real figures.

## Content outside the database

The start-a-business checklist is content, kept in versioned files under `resources/content/checklists/` so every change is reviewed. Each item has `id`, `title`, `where`, `applies_when`, `order`, `source_url`, `verified_on`, `verified_by`. An item without `source_url` and `verified_on` is never shown, and a test enforces it.

## Seeder requirements

The simulated dataset must make the demo walkthrough work every time. It is seeded only when `CAGAYAN_SIMULATED` is true (decisions.md, D37); `db:seed` skips it otherwise. With the fixed seed it contains:

1. All 49 barangays with real names, urban or rural class, and 2024 population.
2. All Cagayan municipalities as shapes, with only Tuguegarao connected.
3. Seven years of history, 2020 through 2026.
4. More businesses in dense urban barangays than rural ones.
5. At least one barangay with fewer than 5 active businesses (small-group rule).
6. At least one barangay where one business exceeds the dominance threshold.
7. At least one barangay and one business type with non-renewals rising two years in a row (early warning).
8. At least one barangay clearly below its peer median.
9. Every business point inside its barangay's boundary, placed like a town: shops along a main street through the barangay's center on alternating sides, farms along a longer cross street (26-10-29). No random points.
10. Wishes for several types, with at least one type wished for often and rarely registered.
11. One administrator for the demo, with the password from `.env`, never in code.

12. The presentation story in `cagayan.simulation.trends`: new food businesses near schools and new online businesses grow every year, and the declining barangay meets item 7.
13. Records in every review state (draft, submitted, returned), with no history, so they stay out of every statistic; permits for every active year; placeholder documents reading "Simulated data".
14. At most `simulation.max_per_barangay` (20) records per barangay, pending ones included, each with a local-style name unique in its barangay (26-10-29).

Items 5 through 7, 9, 12, 13 and 14 each have a test, so a seeder change cannot silently break the demo.

### Transactional Finance Publication "26-10-28"

`RefreshFinanceMetrics` executes the existing finance SQL inside a transaction and serializes rebuilds using an exclusive lock on `stats_finance_year`. Successful finance imports and review batches invoke it once before their transaction commits. `metrics:refresh` uses the same operation. Refresh failure rolls back ledger publication and review activity logging. Raw entry remains pending; it does not publish. Public Home and Capitol economic maps read these same annual results through FinanceMapQuery.
