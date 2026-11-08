<?php

namespace App\Queries;

use App\Data\PublicBusinessDirectory;
use App\Data\PublicBusinessProfile;
use App\Data\PublicBusinessRow;
use App\Data\PublicTownBusinesses;
use App\Data\SelectOption;
use App\Enums\PermitStatus;
use App\Models\Business;
use App\Services\BusinessVisibility;
use App\Services\OperatingMilestone;
use App\Services\RenewalStreak;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Approved records only (P15), public-class fields only (P8). Home-based businesses never send a point. */
final class PublicBusinessesQuery
{
    public function directory(): PublicBusinessDirectory
    {
        return new PublicBusinessDirectory(
            towns: array_values(DB::table('municipalities as m')
                ->whereExists(fn ($query) => $query->from('barangays as g')->whereColumn('g.municipality_id', 'm.id')->whereNotNull('g.boundary'))
                ->orderBy('m.name')->get(['m.psgc_code', 'm.name'])
                ->map(fn (stdClass $town) => new SelectOption((string) $town->psgc_code, (string) $town->name))->all()),
            categories: array_values(DB::table('business_categories')->orderBy('sort_order')->get(['code', 'name', 'color'])
                ->map(fn (stdClass $category) => ['code' => (string) $category->code, 'name' => (string) $category->name, 'color' => (int) $category->color])->all()),
        );
    }

    public function town(string $code): PublicTownBusinesses
    {
        $rows = $this->base()->where('m.psgc_code', $code)
            ->orderByRaw('lower(b.business_name)')
            ->select('b.public_id', 'b.business_name', 'c.code as category', 'g.psgc_code as barangay', 'b.bir_verified_on', 'b.is_storefront')
            ->selectRaw(OperatingMilestone::SQL.' as milestone', OperatingMilestone::bindings())
            ->selectRaw('round(ST_X(b.location)::numeric, 5) as lng, round(ST_Y(b.location)::numeric, 5) as lat')
            ->get();

        return new PublicTownBusinesses(
            town: $code,
            townName: (string) DB::table('municipalities')->where('psgc_code', $code)->value('name'),
            rows: array_values($rows->map(fn (stdClass $row) => new PublicBusinessRow(
                $row->public_id, $row->business_name, $row->category, $row->barangay,
                $row->bir_verified_on !== null, $row->milestone === null ? null : (int) $row->milestone,
                $row->is_storefront ? [(float) $row->lng, (float) $row->lat] : null,
            ))->all()),
            barangays: array_values(DB::table('barangays as g')->join('municipalities as m', 'm.id', '=', 'g.municipality_id')
                ->where('m.psgc_code', $code)->whereNotNull('g.boundary')->orderBy('g.name')->get(['g.psgc_code', 'g.name'])
                ->map(fn (stdClass $barangay) => new SelectOption((string) $barangay->psgc_code, (string) $barangay->name))->all()),
        );
    }

    public function profile(Business $business): PublicBusinessProfile
    {
        $row = $this->base()->where('b.id', $business->id)
            ->select('b.business_name', 't.name as type', 'c.code as category_code', 'c.name as category', 'g.id as barangay_id',
                'g.name as barangay', 'g.psgc_code as barangay_code', 'm.name as town', 'm.psgc_code as town_code', 'b.description',
                'b.year_established', 'b.is_storefront', 'b.business_phone', 'b.business_email', 'b.bir_verified_on', 'permit.valid_until')
            ->selectRaw(PermitStatus::SQL.' as permit_status', PermitStatus::bindings())
            ->selectRaw(RenewalStreak::SQL.' as streak', RenewalStreak::bindings())
            ->selectRaw(OperatingMilestone::SQL.' as milestone', OperatingMilestone::bindings())
            ->selectRaw('ST_X(b.location) as lng, ST_Y(b.location) as lat')
            ->first() ?? abort(404);

        return new PublicBusinessProfile(
            publicId: $business->public_id,
            name: $row->business_name,
            type: $row->type,
            category: ['code' => $row->category_code, 'name' => $row->category],
            barangay: $row->barangay,
            barangayCode: $row->barangay_code,
            town: $row->town,
            townCode: $row->town_code,
            description: $row->description,
            yearEstablished: (int) $row->year_established,
            businessPhone: $row->business_phone,
            businessEmail: $row->business_email,
            birVerifiedOn: $row->bir_verified_on,
            permitStatus: $row->permit_status,
            permitValidUntil: $row->valid_until,
            streak: (int) $row->streak,
            milestone: $row->milestone === null ? null : (int) $row->milestone,
            location: $row->is_storefront ? ['lng' => (float) $row->lng, 'lat' => (float) $row->lat] : null,
            nearby: array_values($this->base()->where('b.barangay_id', $row->barangay_id)->where('b.id', '!=', $business->id)
                ->orderByRaw('(c.code = ?) desc', [$row->category_code])->orderBy('b.business_name')->limit(6)
                ->get(['b.public_id', 'b.business_name', 'c.code', 'c.name'])
                ->map(fn (stdClass $near) => [
                    'id' => (string) $near->public_id, 'name' => (string) $near->business_name,
                    'category' => ['code' => (string) $near->code, 'name' => (string) $near->name],
                ])
                ->all()),
            neighbors: array_values($this->base()->where('b.barangay_id', $row->barangay_id)->where('b.id', '!=', $business->id)
                ->where('b.is_storefront', true)
                ->selectRaw('b.public_id, round(ST_X(b.location)::numeric, 5) as lng, round(ST_Y(b.location)::numeric, 5) as lat')->get()
                ->map(fn (stdClass $near) => ['id' => (string) $near->public_id, 'point' => [(float) $near->lng, (float) $near->lat]])->all()),
        );
    }

    private function base(): Builder
    {
        return BusinessVisibility::public(DB::table('businesses as b'))
            ->join('business_types as t', 't.id', '=', 'b.business_type_id')
            ->join('business_categories as c', 'c.id', '=', 't.business_category_id')
            ->join('barangays as g', 'g.id', '=', 'b.barangay_id')
            ->join('municipalities as m', 'm.id', '=', 'g.municipality_id')
            ->leftJoinLateral(
                DB::table('business_permits as p')->whereColumn('p.business_id', 'b.id')->orderByDesc('p.year')->limit(1)->select('p.valid_until'),
                'permit',
            );
    }
}
