<?php

/*
|--------------------------------------------------------------------------
| Cagayan Business
|--------------------------------------------------------------------------
|
| Every threshold the product uses lives here, never as a literal in PHP,
| SQL, or React. The values and their meaning come from
| docs/engineering/metrics.md and docs/engineering/privacy-rules.md.
| Values marked "open decision" are defaults from decisions.md that the
| team still has to confirm with the adviser.
|
*/

return [

    /*
    | Off by default (decisions.md, D37). When on, the simulated business records are seeded and shown,
    | and the "Simulated data" label appears on every screen and export (P13). Presentation use only.
    */
    'simulated' => (bool) env('CAGAYAN_SIMULATED', false),

    'performance' => [
        'demo_town' => '0201529000',
        // Fiscal years shown on the analytics pages; 2019 comes from the 2020 reports' prior-year column.
        'first_year' => 2019,
        'last_year' => 2025,
        'recent_first_year' => 2023,
    ],

    'privacy' => [

        /*
        | P2. A sales figure for fewer active businesses than this is hidden.
        */
        'small_group_min' => 5,

        /*
        | P3. A sales figure is hidden when the largest single business holds
        | more than this share of the group's total. Open decision O3.
        */
        'dominance_max_share' => 0.70,

        /*
        | M15. Lower edges of the public sales bands, in pesos, ascending.
        | The first band is "under" the first edge, the last is "over" the
        | last edge. Open decision O2.
        */
        'sales_bands' => [
            10_000_000,
            50_000_000,
            100_000_000,
            500_000_000,
            1_000_000_000,
        ],

    ],

    'peer' => [

        /*
        | M16. "Below peer average" when density < median * (1 - margin).
        | Open decision O4.
        */
        'margin' => 0.20,

    ],

    'outlook' => [

        /*
        | M11, M12. How many recent years the middle estimate averages.
        | Open decision O7.
        */
        'lookback_years' => 3,

    ],

    'early_warning' => [

        /*
        | M14. Minimum non-renewals in the latest year before a rising trend
        | raises a flag, so tiny counts do not trigger it.
        */
        'min_non_renewals' => 3,

    ],

    'specialization' => [

        /*
        | M8. Minimum active businesses of a type in a barangay before its
        | specialization is shown.
        */
        'min_type_count' => 3,

    ],

    /*
    | The simulated business dataset (database.md, "Seeder requirements"). A fixed
    | seed keeps the demo identical on every machine.
    */
    'simulation' => [
        'seed' => 20261028,
        'businesses_per_thousand_residents' => 2,
        'minimum_per_barangay' => 3,
        // No barangay lists more than this many records, pending ones included.
        'max_per_barangay' => 20,
        // The presentation story: food near schools and home-based online work grow each year, one barangay declines.
        'trends' => [
            'school_barangays' => ['Carig Norte', 'Carig Sur', 'Ugac Norte', 'Ugac Sur', 'Caritan Centro', 'Centro 10'],
            'food_types' => ['coffee-shop', 'restaurant', 'eatery', 'bakery'],
            'food_new_per_barangay' => 1,
            'food_yearly_growth' => 0.2,
            'online_types' => ['online-seller', 'digital-services'],
            'online_first_year' => 2021,
            'online_new_first_year' => 3,
            'online_yearly_growth' => 0.3,
            'declining_barangay' => 'Linao East',
            // Non-renewals per year in the declining barangay, rising so M14 fires (seeder requirement 7).
            'declining_non_renewals' => [2024 => 3, 2025 => 4, 2026 => 5],
            'pending' => ['submitted' => 10, 'returned' => 4, 'draft' => 5],
        ],
    ],

    'data' => [

        'first_year' => 2020,

        'latest_year' => 2026,

    ],

    'towns' => [

        /*
        | Map shades. Towns are split into this many equal-count groups by
        | population, and the dots get darker with the group (metrics.md, M18).
        */
        'shades' => 5,

    ],

    /*
    | Dates written into messages, such as a wish cooldown, use residents'
    | local time. The database stores UTC.
    */
    'display_timezone' => 'Asia/Manila',

    'admin' => [
        'idle_seconds' => 1800,
        'absolute_seconds' => 28800,
        'warning_seconds' => 120,
        'activity_interval_seconds' => 60,
        'activity_requests_per_minute' => 30,

        /*
        | D20. The login page lives at this path and nowhere else. It is never
        | linked from a public page. Set a long random value in .env on any
        | machine others can reach.
        */
        'login_path' => env('CAGAYAN_ADMIN_LOGIN_PATH', 'staff-sign-in'),

        /*
        | Login attempts allowed per email and network address per minute, and
        | per network address per hour.
        */
        'login_per_minute' => 5,

        'login_per_hour_per_ip' => 20,

        /*
        | Lockout. Failed attempts on one account per hour from any address.
        */
        'login_per_hour_per_account' => 10,

    ],

    'wishes' => [

        /*
        | D21 and P12. Days a browser waits between two new wishes.
        */
        'cooldown_days' => 3,

        /*
        | Edits a browser may make within the rolling edit window.
        */
        'edits_per_window' => 3,

        'edit_window_days' => 7,

        /*
        | Wish submissions and edits per network address per hour. The address
        | is held in the cache only, never written to a table.
        */
        'per_hour_per_ip' => 10,

        /*
        | New respondents (browsers with no earlier suggestion) one network may
        | create per day, counted by the daily network hash. Stops clearing
        | cookies from becoming a way around the cooldown, while a shared
        | network such as a school or the booth wifi still works.
        */
        'new_respondents_per_network_per_day' => 25,

        /*
        | Bot check. A signed timestamp issued with the form must be at least
        | this old when the form comes back, and not older than the maximum.
        */
        'min_fill_seconds' => 3,

        'max_fill_minutes' => 120,

        /*
        | Lifetime of the anonymous submitter cookie.
        */
        'cookie_days' => 30,

        'reason_max' => 255,

        'specific_business_max' => 120,

    ],

    /*
    | The demo accounts the seeder creates, one per role, keyed by role value.
    | Names, emails, and passwords come from .env and are never written in code
    | (database.md, seeder item 11). A role without an email and password is skipped.
    */
    'demo_accounts' => [
        'super_admin' => [
            'name' => env('DEMO_SUPER_ADMIN_NAME', 'Demo Super Admin'),
            'email' => env('DEMO_SUPER_ADMIN_EMAIL'),
            'password' => env('DEMO_SUPER_ADMIN_PASSWORD'),
        ],
        'admin' => [
            'name' => env('DEMO_ADMIN_NAME', 'Demo Administrator'),
            'email' => env('DEMO_ADMIN_EMAIL'),
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ],
        'staff' => [
            'name' => env('DEMO_STAFF_NAME', 'Demo Staff'),
            'email' => env('DEMO_STAFF_EMAIL'),
            'password' => env('DEMO_STAFF_PASSWORD'),
        ],
    ],

    /*
    | Business registry (D40, D41). Documents follow the city's accepted file types. Contact numbers are stored as
    | typed: mobile 09XXXXXXXXX or +639XXXXXXXXX, or a landline of digits, spaces, parentheses and a plus sign.
    */
    'registry' => [
        'permit_expiring_days' => 30,
        // M34 Operating Milestone badges, in years since the business was established.
        'milestones' => [5, 10, 20],
        'document_max_kb' => 5120,
        'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'documents_max' => 12,
        'document_requests_per_minute' => 60,
        'description_max' => 280,
        'address_max' => 160,
        'owner_name_max' => 80,
        'email_max' => 120,
        'review_note_max' => 280,
        'phone_pattern' => '/^(09\d{9}|\+639\d{9}|[\d\s()+]{7,20})$/',
        'tin_pattern' => '/^\d{3}-\d{3}-\d{3}(-\d{3,5})?$/',
        'rdo_pattern' => '/^\d{3}[A-Z]?$/',
        'registration_number_max' => 30,
        'permit_number_max' => 30,
    ],

    'inputs' => [
        'display_name_max' => 100,
        'business_name_max' => 55,
        'category_name_max' => 60,
        'business_type_name_max' => 60,
    ],

    /*
    | Business Categories (D39). Category colors are slots in the --category-N palette of
    | app.css, and the page loads every type, so the list is capped.
    */
    'categories' => ['colors' => 10, 'max_types' => 300, 'page_size' => 10],

    /*
    | Finance ledger entry and review (D35, metrics.md M19 to M27). Fiscal years
    | the forms accept, the largest peso amount, and the note length.
    */
    'finances' => [
        'first_year' => 2019,
        'last_year' => 2025,
        'value_max' => '999999999999.99',
        'coa_url' => 'https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/',
        'source_url_max' => 500,
        'review_batch_max' => 200,
        'note_max' => 500,
        // Workbook citations list every contributing cell, such as 'G61:G64 and K61:K64' (26-11-03).
        'location_max' => 255,
        'per_page' => 20,
        'source_requests_per_minute' => 60,
        // Towns whose workbook breakdowns finances:import loads (26-10-28: Tuguegarao first). Other towns'
        // parsed workbooks stay in data/towns/finances/extracted; every Executive Summary always loads.
        'workbook_towns' => ['0201529000'],
        // Executive Summary lines beyond the totals (26-10-29: Tuguegarao first, metrics.md M35). Other towns
        // keep them in data/towns/finances/extracted until they are added here.
        'summary_detail_towns' => ['0201529000'],
        'summary_detail_lines' => [
            'position.assets', 'position.liabilities', 'position.equity',
            'budget.allotments_current', 'budget.allotments_continuing', 'budget.obligations_continuing',
        ],
        'groups' => [
            'position' => ['label' => 'Financial Condition', 'bases' => ['actual']],
            'totals' => ['label' => 'Results of Operation', 'bases' => ['actual']],
            'budget' => ['label' => 'Budget Execution', 'bases' => ['actual']],
            'revenue_sources' => ['label' => 'Revenue Sources', 'bases' => ['actual']],
            'business_taxes' => ['label' => 'Business Taxes and Fees', 'bases' => ['actual']],
            'development' => ['label' => 'Budget and Actual Amounts', 'bases' => ['actual', 'budget_final']],
        ],
    ],

];
