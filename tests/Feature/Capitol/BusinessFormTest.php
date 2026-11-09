<?php

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->town = Municipality::factory()->create();
    $this->barangay = Barangay::factory()->for($this->town)->create();
    // A small square around central Tuguegarao.
    DB::update("update barangays set boundary = ST_Multi(ST_GeomFromText('POLYGON((121.72 17.61, 121.73 17.61, 121.73 17.62, 121.72 17.62, 121.72 17.61))', 4326)) where id = ?", [$this->barangay->id]);
    $this->type = BusinessType::factory()->create(['code' => 'coffee-shop']);
    $this->staff = User::factory()->create(['role' => UserRole::Staff]);
    $this->staff->assignedTowns()->attach($this->town);
});

function businessInput(array $overrides = []): array
{
    return $overrides + [
        'submit' => true, 'business_name' => 'Kape ni Maria', 'business_type' => 'coffee-shop',
        'description' => 'Coffee near the university.', 'year_established' => 2024, 'is_storefront' => true,
        'barangay' => test()->barangay->psgc_code, 'street_address' => '12 Rizal Street', 'lng' => 121.725, 'lat' => 17.615,
        'business_phone' => '09171234567', 'business_email' => null, 'owner_name' => 'Maria Santos', 'contact_number' => '09181234567',
        'ownership_type' => 'sole_proprietorship', 'size_category' => 'micro', 'employee_count' => 3,
        'registration_body' => 'dti', 'registration_number' => '1234567', 'registration_valid_until' => '2029-05-01',
        'tin' => '123-456-789-000', 'rdo_code' => '013',
        'permit_number' => 'BP-2026-00001', 'permit_valid_from' => '2026-01-01', 'permit_valid_until' => '2026-12-31',
        'documents' => [['kind' => 'bir_cor', 'file' => UploadedFile::fake()->create('cor.pdf', 200, 'application/pdf')]],
    ];
}

it('lets staff submit a record with its pin, permit and private documents', function () {
    $response = $this->actingAs($this->staff)->post(route('capitol.businesses.store'), businessInput());

    $business = Business::query()->where('business_name', 'Kape ni Maria')->firstOrFail();
    $response->assertRedirect(route('capitol.businesses.show', $business));
    $document = $business->documents()->firstOrFail();

    expect($business)->record_status->toBe(RecordStatus::Submitted)->is_simulated->toBeFalse()->submitted_by->toBe($this->staff->id)
        ->and($business->permits()->value('year'))->toBe(2026)
        ->and($document->path)->toStartWith("registry/{$business->public_id}/")
        ->and($document->sha256)->toHaveLength(64)
        ->and(Storage::disk('local')->exists($document->path))->toBeTrue()
        ->and(DB::table('activity_logs')->where('event', 'business_submitted')->count())->toBe(1);
});

it('rejects a pin outside the barangay, another town, and a document of the wrong type', function () {
    $other = Barangay::factory()->create();

    $this->actingAs($this->staff)->post(route('capitol.businesses.store'), businessInput(['lng' => 121.80]))->assertSessionHasErrors('lng');
    $this->actingAs($this->staff)->post(route('capitol.businesses.store'), businessInput(['barangay' => $other->psgc_code]))->assertSessionHasErrors('barangay');
    $this->actingAs($this->staff)->post(route('capitol.businesses.store'), businessInput([
        'documents' => [['kind' => 'photo', 'file' => UploadedFile::fake()->create('photo.exe', 10)]],
    ]))->assertSessionHasErrors('documents.0.file');

    expect(Business::query()->count())->toBe(0);
});

it('keeps adding to staff, and locks a record while it waits for review', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.businesses.create'))->assertForbidden();
    $this->actingAs($this->staff)->get(route('capitol.businesses.create'))->assertOk();

    $business = Business::factory()->inBarangay($this->barangay)->status(RecordStatus::Submitted)->create(['is_simulated' => false]);
    $this->actingAs($this->staff)->get(route('capitol.businesses.edit', $business))->assertForbidden();

    $business->forceFill(['record_status' => RecordStatus::Returned, 'review_note' => 'Fix the pin.'])->save();
    $this->actingAs($this->staff)->put(route('capitol.businesses.update', $business), businessInput(['documents' => []]))
        ->assertRedirect(route('capitol.businesses.show', $business));

    expect($business->fresh())->record_status->toBe(RecordStatus::Submitted)->review_note->toBeNull();
});
