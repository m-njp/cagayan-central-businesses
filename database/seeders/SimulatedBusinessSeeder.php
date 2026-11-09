<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\BusinessType;
use App\Models\Municipality;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Simulated businesses for Tuguegarao City, 2020 to 2026, labeled "Simulated data" (D10, D37).
 * It meets database.md "Seeder requirements" 3 to 7 and 9, follows the business_years status rules, and tells the
 * presentation story in cagayan.simulation.trends: food near schools and online work grow, one barangay declines.
 * No barangay holds more than cagayan.simulation.max_per_barangay records, and every name is unique in its barangay.
 */
class SimulatedBusinessSeeder extends Seeder
{
    /** Relative weights; farms and agri-supply weigh more in less populous barangays. */
    private const array WEIGHTS = [
        'sari-sari-store' => 30, 'eatery' => 12, 'grocery' => 6, 'pharmacy' => 4, 'hardware-store' => 3,
        'clothing-store' => 5, 'school-supplies' => 3, 'auto-repair' => 4, 'bakery' => 5, 'water-refilling' => 4,
        'printing-shop' => 2, 'coffee-shop' => 4, 'restaurant' => 4, 'boarding-house' => 4, 'hotel' => 1,
        'bank' => 1, 'lending' => 3, 'veterinary-clinic' => 1, 'tutorial-center' => 2, 'clinic' => 2,
        'dental-clinic' => 2, 'gym' => 1, 'salon' => 6, 'laundry' => 3, 'repair-shop' => 3,
        'crop-farm' => 4, 'livestock-farm' => 3, 'fishpond' => 1, 'agri-supply' => 2, 'rice-mill' => 1,
    ];

    private const array RURAL_TYPES = ['crop-farm', 'livestock-farm', 'fishpond', 'agri-supply', 'rice-mill'];

    /** @var array<string, array{0: int, 1: int, 2: int, 3: int}> lowest and highest yearly sales, then employees */
    private const array SIZES = [
        'micro' => [200_000, 2_500_000, 1, 9],
        'small' => [3_000_000, 15_000_000, 10, 49],
        'medium' => [15_000_000, 60_000_000, 50, 199],
    ];

    private const array RETURN_NOTES = [
        'Upload a clearer copy of the BIR Certificate of Registration.',
        'The pin is outside the storefront. Move it to the business address.',
        'The DTI registration has expired. Upload the renewed certificate.',
        'Add this year\'s barangay clearance.',
    ];

    private const string DOCUMENT = 'registry/simulated-document.pdf';

    /** Local-style names by type: {last} and {first} are owner names. A type without patterns reads "{last} {Type}". */
    private const array NAMES = [
        'sari-sari-store' => ["{first}'s Sari-Sari Store", '{last} Mini Store', '{first} Variety Store'],
        'grocery' => ['{last} Grocery', '{first} Supermart', '{last} Mart'],
        'pharmacy' => ['{last} Drugstore', '{first} Pharmacy', '{last} Botika'],
        'hardware-store' => ['{last} Hardware', '{last} Construction Supply', '{first} Builders Depot'],
        'clothing-store' => ['{first} Boutique', '{last} Fashion', '{first} Apparel'],
        'school-supplies' => ['{last} School and Office Supplies', '{first} Bookstore'],
        'auto-repair' => ['{last} Auto Repair', '{first} Vulcanizing Shop', '{last} Motorparts'],
        'bakery' => ['{last} Bakeshop', "{first}'s Bakery", '{last} Panaderia'],
        'water-refilling' => ['{last} Water Station', '{first} Purified Water'],
        'printing-shop' => ['{last} Printing Services', '{first} Print and Copy'],
        'coffee-shop' => ['Kape ni {first}', '{last} Coffee', '{first} Brew House'],
        'restaurant' => ['{last} Restaurant', 'Casa {last}', "{first}'s Kitchen"],
        'eatery' => ['Kusina ni {first}', '{last} Carinderia', 'Lutong Bahay ni {first}'],
        'boarding-house' => ['{last} Boarding House', '{first} Dormitory', '{last} Residences'],
        'hotel' => ['Hotel {last}', '{last} Inn', '{last} Pension House'],
        'bank' => ['{last} Rural Bank'],
        'lending' => ['{last} Lending', '{first} Pawnshop', '{last} Credit'],
        'veterinary-clinic' => ['{last} Veterinary Clinic', '{first} Pet Clinic'],
        'tutorial-center' => ['{last} Learning Center', '{first} Tutorial Center'],
        'clinic' => ['{last} Medical Clinic', 'Dr. {last} Clinic'],
        'dental-clinic' => ['{last} Dental Clinic', 'Dr. {last} Dental'],
        'gym' => ['{last} Fitness Gym', '{first} Fitness Hub'],
        'salon' => ['{first} Beauty Salon', "{first}'s Salon and Spa", '{last} Hair Studio'],
        'laundry' => ['{last} Laundry Hub', 'Labada ni {first}', '{first} Wash and Fold'],
        'repair-shop' => ['{first} Gadget Repair', '{last} Electronics Repair'],
        'crop-farm' => ['{last} Farm', '{last} Rice and Corn Farm'],
        'livestock-farm' => ['{last} Piggery', '{last} Poultry Farm'],
        'fishpond' => ['{last} Fishpond'],
        'agri-supply' => ['{last} Agri Supply', '{first} Agrivet'],
        'rice-mill' => ['{last} Rice Mill'],
        'online-seller' => ["{first}'s Online Shop", '{first} Online Store'],
        'digital-services' => ['{first} Creatives', '{last} Digital Studio'],
    ];

    /** Short owner-style descriptions by type, so profiles do not repeat the type and the place. */
    private const array DESCRIPTIONS = [
        'sari-sari-store' => 'Snacks, drinks, load and everyday household needs.',
        'grocery' => 'Fresh produce, canned goods and toiletries.',
        'pharmacy' => 'Prescription and over-the-counter medicines.',
        'hardware-store' => 'Construction materials, tools and electrical supplies.',
        'clothing-store' => 'Casual wear, school uniforms and footwear.',
        'school-supplies' => 'Notebooks, books and art materials for students.',
        'auto-repair' => 'Tune-ups, vulcanizing and motorcycle parts.',
        'bakery' => 'Pandesal baked fresh every morning, plus cakes on order.',
        'water-refilling' => 'Purified drinking water with home delivery.',
        'printing-shop' => 'Printing, photocopy, lamination and tarpaulins.',
        'coffee-shop' => 'Brewed coffee and pastries, with study-friendly seating.',
        'restaurant' => 'Filipino and Ibanag favorites for dine-in and takeout.',
        'eatery' => 'Affordable home-style meals, lunch and dinner.',
        'boarding-house' => 'Rooms for students and workers, with water and electricity.',
        'hotel' => 'Air-conditioned rooms for travelers and events.',
        'bank' => 'Deposits, loans and remittances.',
        'lending' => 'Small loans and pawning of jewelry and gadgets.',
        'veterinary-clinic' => 'Checkups, vaccines and grooming for pets.',
        'tutorial-center' => 'Review classes and tutoring for all grade levels.',
        'clinic' => 'General consultations and basic laboratory tests.',
        'dental-clinic' => 'Cleaning, fillings, extractions and braces.',
        'gym' => 'Weights, cardio and personal training.',
        'salon' => 'Haircuts, color, rebond and nail care.',
        'laundry' => 'Wash, dry and fold by the kilo.',
        'repair-shop' => 'Phone, laptop and appliance repair.',
        'crop-farm' => 'Rice and corn harvest sold to local traders.',
        'livestock-farm' => 'Poultry and hogs for local markets.',
        'fishpond' => 'Tilapia and bangus, harvested to order.',
        'agri-supply' => 'Seeds, fertilizer and farm chemicals.',
        'rice-mill' => 'Milling services and rice by the sack.',
        'online-seller' => 'Orders taken online, with pickup or delivery in the city.',
        'digital-services' => 'Logos, layouts and social media content.',
    ];

    private Generator $faker;

    /** @var array<int, string> */
    private array $typeNames;

    /** @var array<int, string> */
    private array $typeCodes;

    /** @var array<int, int> records each barangay can still take */
    private array $room = [];

    /** @var array<int, array<string, true>> names already used in each barangay */
    private array $names = [];

    public function run(): void
    {
        $seed = (int) config('cagayan.simulation.seed');
        mt_srand($seed);
        $this->faker = Faker::create('en_PH');
        $this->faker->seed($seed);
        $trends = config('cagayan.simulation.trends');

        $town = Municipality::query()->where('psgc_code', config('cagayan.performance.demo_town'))->firstOrFail();
        $barangays = Barangay::query()->where('municipality_id', $town->id)->whereNotNull('boundary')
            ->orderBy('psgc_code')->get(['id', 'name', 'population']);
        $types = BusinessType::query()->pluck('id', 'code');
        $this->typeNames = BusinessType::query()->pluck('name', 'id')->all();
        $this->typeCodes = BusinessType::query()->pluck('code', 'id')->all();
        $first = (int) config('cagayan.data.first_year');
        $last = (int) config('cagayan.data.latest_year');
        $minimum = (int) config('cagayan.simulation.minimum_per_barangay');
        $cap = (int) config('cagayan.simulation.max_per_barangay');
        $this->room = $barangays->mapWithKeys(fn (Barangay $b) => [$b->id => $cap])->all();
        $cohorts = array_map(fn (int $year) => (int) round($trends['food_new_per_barangay'] * (1 + $trends['food_yearly_growth']) ** ($year - $first)), range($first, $last));
        $medianPopulation = $barangays->pluck('population')->filter()->median() ?? 0;

        // Requirement 5: the least populous barangay keeps a small group. Requirement 6: one barangay has a dominant business.
        $smallest = $barangays->sortBy('population')->first()?->id;
        $dominant = $barangays->sortBy('population')->values()->get(intdiv($barangays->count(), 2))?->id;
        $declining = $barangays->firstWhere('name', $trends['declining_barangay'])?->id;

        $businesses = [];
        $histories = [];
        foreach ($barangays as $barangay) {
            $school = in_array($barangay->name, $trends['school_barangays'], true);
            $count = match (true) {
                $barangay->id === $smallest => $minimum,
                $barangay->id === $declining => $cap,
                default => min(max($minimum, (int) round(($barangay->population ?? 0) / 1000 * (int) config('cagayan.simulation.businesses_per_thousand_residents'))),
                    $cap - ($school ? array_sum($cohorts) : 0)),
            };
            $rural = ($barangay->population ?? 0) < $medianPopulation;
            // Food in school barangays comes only from the yearly cohorts below, so its growth reads clearly.
            $excluded = $school ? $trends['food_types'] : [];
            // Requirement 7: the declining barangay loses established businesses at a rising pace; the rest keep renewing.
            $endings = $barangay->id === $declining ? $this->endings($trends['declining_non_renewals']) : [];

            for ($i = 0; $i < $count; $i++) {
                $isDominant = $barangay->id === $dominant && $i === 0;
                $ending = array_shift($endings);
                $established = $isDominant || $ending !== null || mt_rand(1, 10) <= 3 ? mt_rand(2000, $first - 1) : mt_rand($first, $last);
                $fate = $barangay->id === $declining || $isDominant ? ($ending ?? 0) : null;
                $this->add($businesses, $histories, $barangay, $types[$this->pickType($rural, $excluded)], $established, $first, $last,
                    size: $isDominant ? 'medium' : null, sales: $isDominant ? 400_000_000 : null, fate: $fate);
            }
        }

        // Food near schools: each year brings more new food businesses than the last, and they keep renewing.
        foreach ($barangays->whereIn('name', $trends['school_barangays']) as $barangay) {
            foreach ($cohorts as $offset => $new) {
                $year = $first + $offset;
                for ($i = 0; $i < $new; $i++) {
                    $this->add($businesses, $histories, $barangay, $types[$trends['food_types'][mt_rand(0, count($trends['food_types']) - 1)]],
                        $year, $first, $last, fate: 0);
                }
            }
        }

        // Online and home-based services: city-wide, growing every year from their first year. The small group and the
        // declining barangay keep their story, so these go elsewhere.
        $byPopulation = $barangays->whereNotIn('id', [$declining, $smallest])->mapWithKeys(fn (Barangay $b) => [$b->id => (int) $b->population])->all();
        for ($year = $trends['online_first_year']; $year <= $last; $year++) {
            $new = (int) round($trends['online_new_first_year'] * (1 + $trends['online_yearly_growth']) ** ($year - $trends['online_first_year']));
            for ($i = 0; $i < $new; $i++) {
                $barangay = $barangays->firstWhere('id', $this->withRoom($byPopulation));
                $this->add($businesses, $histories, $barangay, $types[$trends['online_types'][mt_rand(0, count($trends['online_types']) - 1)]],
                    $year, $first, $last, storefront: false);
            }
        }

        // Records waiting in the registry workflow; they have no history and stay out of every statistic (P15).
        $pending = [];
        foreach ($trends['pending'] as $status => $total) {
            for ($i = 0; $i < $total; $i++) {
                $barangay = $barangays->firstWhere('id', $this->withRoom($byPopulation));
                $this->room[$barangay->id]--;
                $row = $this->business($barangay, $types[$this->pickType(false)], $last, $this->pickSize(), true);
                $pending[] = array_merge($row, [
                    'record_status' => $status,
                    'submitted_at' => $status === 'draft' ? null : now()->subDays(mt_rand(1, 20)),
                    'review_note' => $status === 'returned' ? self::RETURN_NOTES[$i % count(self::RETURN_NOTES)] : null,
                    'reviewed_at' => $status === 'returned' ? now()->subDays(mt_rand(0, 3)) : null,
                ]);
            }
        }

        [$size, $sha256] = $this->storeDocument();

        DB::transaction(function () use ($businesses, $histories, $pending, $last, $size, $sha256): void {
            foreach (array_chunk($businesses, 300) as $chunk) {
                DB::table('businesses')->insert($chunk);
            }
            DB::table('businesses')->insert($pending);
            $ids = DB::table('businesses')->pluck('id', 'public_id');
            $rows = [];
            foreach ($histories as $publicId => $years) {
                foreach ($years as $year) {
                    $rows[] = $year + ['business_id' => $ids[$publicId], 'created_at' => now(), 'updated_at' => now()];
                }
            }
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('business_years')->insert($chunk);
            }
            $this->place();

            // Mayor's permits run January to December for every active year.
            DB::statement("insert into business_permits (business_id, permit_number, year, valid_from, valid_until, created_at, updated_at)
                select y.business_id, 'BP-' || y.year || '-' || lpad((row_number() over (partition by y.year order by y.business_id))::text, 5, '0'),
                    y.year, make_date(y.year, 1, 1), make_date(y.year, 12, 31), now(), now()
                from business_years y where y.status in ('new', 'renewed')");

            // Every simulated document points at one placeholder file that reads "Simulated data".
            $document = "insert into business_documents (public_id, business_id, kind, path, original_name, mime, size, sha256, issued_on, created_at, updated_at)
                select gen_random_uuid(), b.id, ?, ?, ?, 'application/pdf', ?, ?, make_date(?, 1, 15), now(), now() from businesses b where ";
            DB::insert($document."b.record_status = 'approved' and b.id % 8 <> 0", ['bir_cor', self::DOCUMENT, 'certificate-of-registration.pdf', $size, $sha256, $last]);
            DB::insert($document."b.record_status <> 'draft'", ['barangay_clearance', self::DOCUMENT, 'barangay-clearance.pdf', $size, $sha256, $last]);
            DB::insert($document.'exists (select 1 from business_permits p where p.business_id = b.id and p.year = ?)',
                ['business_permit', self::DOCUMENT, 'business-permit.pdf', $size, $sha256, $last, $last]);
            DB::update("update businesses b set bir_verified_on = make_date(?, 1, 20)
                where b.record_status = 'approved' and exists (select 1 from business_documents d where d.business_id = b.id and d.kind = 'bir_cor')", [$last]);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $businesses
     * @param  array<string, list<array{year: int, status: string, declared_gross_sales: string|null}>>  $histories
     *
     * @param-out list<array<string, mixed>> $businesses
     * @param-out array<string, list<array{year: int, status: string, declared_gross_sales: string|null}>> $histories
     */
    private function add(array &$businesses, array &$histories, Barangay $barangay, int $typeId, int $established, int $first, int $last,
        ?string $size = null, ?int $sales = null, ?int $fate = null, bool $storefront = true): void
    {
        $this->room[$barangay->id]--;
        $size ??= $this->pickSize();
        [$low, $high] = self::SIZES[$size];
        $row = $this->business($barangay, $typeId, $established, $size, $storefront);
        $businesses[] = $row;
        $histories[(string) $row['public_id']] = $this->history($established, $first, $last, $sales ?? mt_rand($low, $high), $fate);
    }

    /**
     * @return array<string, mixed>
     */
    private function business(Barangay $barangay, int $typeId, int $established, string $size, bool $storefront): array
    {
        [, , $minStaff, $maxStaff] = self::SIZES[$size];
        $type = $this->typeNames[$typeId];
        $ownership = $this->pick(['sole_proprietorship' => 80, 'partnership' => 8, 'corporation' => 8, 'cooperative' => 4]);
        $body = match ($ownership) {
            'sole_proprietorship' => 'dti',
            'cooperative' => 'cda',
            default => 'sec',
        };
        $name = $this->name($barangay->id, $this->typeCodes[$typeId], $type);

        return [
            'public_id' => (string) Str::uuid(),
            'business_name' => Str::limit($name, 55, ''),
            'business_type_id' => $typeId,
            'description' => self::DESCRIPTIONS[$this->typeCodes[$typeId]] ?? $type,
            'year_established' => $established,
            'barangay_id' => $barangay->id,
            'location' => DB::raw('ST_SetSRID(ST_MakePoint(0, 0), 4326)'),
            'owner_name' => Str::limit($this->faker->name(), 80, ''),
            'contact_number' => '09'.$this->faker->numerify('#########'),
            'ownership_type' => $ownership,
            'size_category' => $size,
            'employee_count' => mt_rand($minStaff, $maxStaff),
            'is_simulated' => true,
            'source' => null,
            'street_address' => Str::limit($this->faker->buildingNumber().' '.$this->faker->streetName(), 160, ''),
            'business_phone' => mt_rand(1, 10) <= 6 ? '09'.$this->faker->numerify('#########') : null,
            'business_email' => mt_rand(1, 10) <= 3 ? Str::slug(Str::limit($name, 30, '')).'@example.com' : null,
            'is_storefront' => $storefront,
            'registration_body' => $body,
            'registration_number' => match ($body) {
                'dti' => $this->faker->numerify('#######'),
                'sec' => 'CS'.$established.$this->faker->numerify('#####'),
                'cda' => '9520-'.$this->faker->numerify('########'),
            },
            // DTI business names last five years; SEC and CDA registrations do not expire.
            'registration_valid_until' => $body === 'dti' ? sprintf('%d-%02d-%02d', 2026 + mt_rand(0, 4), mt_rand(1, 12), mt_rand(1, 28)) : null,
            'tin' => $this->faker->numerify('###-###-###-000'),
            'rdo_code' => '013',
            'record_status' => 'approved',
            'submitted_at' => null,
            'review_note' => null,
            'reviewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** A local-style name, unique within the barangay; ten tries, then the owner's first and last name are both used. */
    private function name(int $barangayId, string $code, string $type): string
    {
        $patterns = self::NAMES[$code] ?? ['{last} '.$type];
        for ($try = 0; $try < 10; $try++) {
            $name = strtr($patterns[mt_rand(0, count($patterns) - 1)], ['{first}' => $this->faker->firstName(), '{last}' => $this->faker->lastName()]);
            if (! isset($this->names[$barangayId][$name])) {
                break;
            }
            $name = $this->faker->firstName().' '.$this->faker->lastName().' '.$type;
        }
        $this->names[$barangayId][$name] = true;

        return $name;
    }

    /**
     * A barangay with room left, weighted by population.
     *
     * @param  array<int, int>  $weights
     */
    private function withRoom(array $weights): int
    {
        return (int) $this->pick(array_filter($weights, fn (int $weight, int $id) => $this->room[$id] > 0, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Requirement 9, placed like a town rather than at random: shops line a main street through the barangay's center,
     * on alternating sides, and farms sit along a longer cross street toward its edges. Both streets follow the
     * barangay's long axis and stay inside it, kept back from the boundary.
     */
    private function place(): void
    {
        DB::statement(<<<'SQL'
            with shape as (
                select g.id, ST_PointOnSurface(g.boundary) as center, g.boundary,
                    case when ST_IsEmpty(i.shrunk) then g.boundary else i.shrunk end as inner,
                    ST_Azimuth(ST_StartPoint(d.line), ST_EndPoint(d.line)) as azimuth, ST_Length(d.line) as span
                from barangays g
                cross join lateral (select ST_LongestLine(g.boundary, g.boundary) as line) d
                cross join lateral (select ST_Buffer(g.boundary::geography, -40)::geometry as shrunk) i
                where g.boundary is not null
            ),
            street as (
                select s.id, k.street, s.inner, s.boundary, s.azimuth + k.street * pi() / 2 as azimuth,
                    (select p.geom from ST_Dump(ST_Intersection(s.inner, ST_MakeLine(
                        ST_Translate(s.center, -sin(s.azimuth + k.street * pi() / 2) * k.half, -cos(s.azimuth + k.street * pi() / 2) * k.half),
                        ST_Translate(s.center, sin(s.azimuth + k.street * pi() / 2) * k.half, cos(s.azimuth + k.street * pi() / 2) * k.half)))) p
                        where GeometryType(p.geom) = 'LINESTRING' order by ST_Length(p.geom) desc limit 1) as line
                from shape s
                cross join lateral (values (0, least(s.span * 0.3, 0.004)), (1, s.span * 0.45)) as k(street, half)
            ),
            ranked as (
                select b.id, b.barangay_id, x.street,
                    row_number() over (partition by b.barangay_id, x.street order by b.id) as n,
                    count(*) over (partition by b.barangay_id, x.street) as total
                from businesses b
                join business_types t on t.id = b.business_type_id
                cross join lateral (select case when t.code in ('crop-farm', 'livestock-farm', 'fishpond', 'rice-mill') then 1 else 0 end as street) x
            )
            -- Setbacks of roughly 10 to 35 m and uneven spacing, fixed by the business id, so the strip reads as buildings.
            update businesses b set location = ST_ClosestPoint(st.inner, ST_Translate(
                    ST_LineInterpolatePoint(st.line, greatest(0, least(1, (r.n - 0.5 + (r.id % 5 - 2) * 0.15) / r.total))),
                    case when r.n % 2 = 0 then 1 else -1 end * cos(st.azimuth) * (0.0001 + (r.id % 7) * 0.00004),
                    case when r.n % 2 = 0 then -1 else 1 end * sin(st.azimuth) * (0.0001 + (r.id % 7) * 0.00004)))
            from ranked r
            join street st on st.id = r.barangay_id and st.street = r.street
            where b.id = r.id and st.line is not null
            SQL);
        // A barangay too small for a street keeps every point at its center, still inside it.
        DB::statement('update businesses b set location = ST_PointOnSurface(g.boundary) from barangays g
            where g.id = b.barangay_id and not ST_Contains(g.boundary, b.location)');
    }

    /**
     * Status rules 1 to 3 in database.md: first row new or renewed, then renewed until one non-renewal or closure.
     * A null fate renews at random, 0 always renews, and a year ends the business with a non-renewal that year.
     *
     * @return list<array{year: int, status: string, declared_gross_sales: string|null}>
     */
    private function history(int $established, int $first, int $last, int $sales, ?int $fate): array
    {
        $years = [];
        for ($year = max($established, $first); $year <= $last; $year++) {
            $status = match (true) {
                $years === [] => $established >= $first ? 'new' : 'renewed',
                $fate === $year => 'not_renewed',
                $fate !== null => 'renewed',
                default => $this->pick(['renewed' => 92, 'not_renewed' => 6, 'closed' => 2]),
            };
            $active = in_array($status, ['new', 'renewed'], true);
            $years[] = ['year' => $year, 'status' => $status, 'declared_gross_sales' => $active ? number_format($sales, 2, '.', '') : null];
            if (! $active) {
                break;
            }
            $sales = (int) round($sales * (1 + mt_rand(-5, 12) / 100));
        }

        return $years;
    }

    /**
     * @param  array<int, int>  $schedule  non-renewals per year
     * @return list<int>
     */
    private function endings(array $schedule): array
    {
        $endings = [];
        foreach ($schedule as $year => $count) {
            array_push($endings, ...array_fill(0, $count, (int) $year));
        }

        return $endings;
    }

    /**
     * A one-page PDF reading "Simulated data", stored once on the private disk.
     *
     * @return array{0: int, 1: string}
     */
    private function storeDocument(): array
    {
        $stream = 'BT /F1 28 Tf 72 760 Td (Simulated data) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
        Storage::disk('local')->put(self::DOCUMENT, $pdf);

        return [strlen($pdf), hash('sha256', $pdf)];
    }

    /**
     * @param  list<string>  $excluded
     */
    private function pickType(bool $rural, array $excluded = []): string
    {
        $weights = array_diff_key(self::WEIGHTS, array_flip($excluded));
        foreach (array_intersect(self::RURAL_TYPES, array_keys($weights)) as $code) {
            $weights[$code] *= $rural ? 3 : 1;
        }

        return $this->pick($weights);
    }

    private function pickSize(): string
    {
        return $this->pick(['micro' => 75, 'small' => 21, 'medium' => 4]);
    }

    /**
     * @param  array<array-key, int>  $weights
     */
    private function pick(array $weights): string
    {
        $roll = mt_rand(1, max(1, array_sum($weights)));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_last($weights);
    }
}
