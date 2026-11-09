<?php

use App\Enums\BusinessStatus;
use App\Enums\DocumentKind;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessYear;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

const PROFILE_SALES = '7654321.98';

beforeEach(function () {
    config(['cagayan.simulated' => true]);
    $this->town = Municipality::factory()->create();
    $this->business = Business::factory()->inBarangay(Barangay::factory()->for($this->town)->create())->create([
        'owner_name' => 'Profile Owner', 'tin' => '123-456-789-000', 'street_address' => '12 Rizal Street',
    ]);
    BusinessYear::factory()->for($this->business)->status(BusinessStatus::Renewed, 2026)->sales(PROFILE_SALES)->create();
});

function registryStaff(?Municipality $town): User
{
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    if ($town) {
        $staff->assignedTowns()->attach($town);
    }

    return $staff;
}

it('shows the full record with a masked TIN and logs exactly one view per open (P9)', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('capitol.businesses.show', $this->business));

    $response->assertInertia(fn (Assert $page) => $page->component('capitol/businesses/show')
        ->where('business.admin.ownerName', 'Profile Owner')
        ->where('business.admin.tin', '***-***-789-000')
        ->where('business.history.0.status', 'renewed'));
    $response->assertDontSee('123-456')->assertDontSee('7654321');
    $this->actingAs($admin)->get(route('capitol.businesses.index'))->assertOk();

    expect(DB::table('record_view_logs')->where(['user_id' => $admin->id, 'business_id' => $this->business->id])->count())->toBe(1);
});

it('is a plain 404 for guests', function () {
    $this->get(route('capitol.businesses.show', $this->business))->assertNotFound();
});

it('lets an admin approve a submitted record and marks the BIR badge when a COR is on file', function () {
    $this->business->forceFill(['record_status' => RecordStatus::Submitted])->save();
    BusinessDocument::factory()->for($this->business)->kind(DocumentKind::BirCor)->create();

    $this->actingAs(User::factory()->create())->post(route('capitol.businesses.reviews.store', $this->business), ['decision' => 'approved'])
        ->assertRedirect(route('capitol.businesses.show', $this->business));

    expect($this->business->fresh())->record_status->toBe(RecordStatus::Approved)->bir_verified_on->not->toBeNull()
        ->and(DB::table('activity_logs')->where('event', 'business_approved')->count())->toBe(1);
});

it('needs a reason to return a record, and only admins review submitted records', function () {
    $this->business->forceFill(['record_status' => RecordStatus::Submitted])->save();
    $admin = User::factory()->create();

    $this->actingAs(registryStaff($this->town))->post(route('capitol.businesses.reviews.store', $this->business), ['decision' => 'approved'])->assertForbidden();
    $this->actingAs($admin)->post(route('capitol.businesses.reviews.store', $this->business), ['decision' => 'returned'])->assertSessionHasErrors('note');
    $this->actingAs($admin)->post(route('capitol.businesses.reviews.store', $this->business), ['decision' => 'returned', 'note' => 'Upload the COR.']);

    expect($this->business->fresh())->record_status->toBe(RecordStatus::Returned)->review_note->toBe('Upload the COR.');
    $this->actingAs($admin)->post(route('capitol.businesses.reviews.store', $this->business), ['decision' => 'approved'])->assertForbidden();
});

it('serves documents to admins and staff of the town only', function () {
    Storage::disk('local')->put('registry/test/cor.pdf', '%PDF-1.4 test');
    $document = BusinessDocument::factory()->for($this->business)->create(['path' => 'registry/test/cor.pdf']);
    $missing = BusinessDocument::factory()->for($this->business)->create(['path' => 'registry/../../.env']);
    $url = fn (BusinessDocument $file) => route('capitol.businesses.documents.show', ['business' => $this->business, 'document' => $file]);

    $this->actingAs(User::factory()->create())->get($url($document))->assertOk();
    $this->actingAs(registryStaff($this->town))->get($url($document))->assertOk();
    $this->actingAs(registryStaff(Municipality::factory()->create()))->get($url($document))->assertForbidden();
    $this->actingAs(User::factory()->create())->get($url($missing))->assertNotFound();

    Storage::disk('local')->deleteDirectory('registry/test');
});

it('hides admin fields, permit numbers and documents from Staff of another town', function () {
    BusinessDocument::factory()->for($this->business)->create();

    $this->actingAs(registryStaff(Municipality::factory()->create()))->get(route('capitol.businesses.show', $this->business))
        ->assertInertia(fn (Assert $page) => $page->where('business.admin', null)->has('business.documents', 0))
        ->assertDontSee('Profile Owner')->assertDontSee('12 Rizal Street');
    $this->actingAs(registryStaff($this->town))->get(route('capitol.businesses.show', $this->business))
        ->assertInertia(fn (Assert $page) => $page->where('business.admin.ownerName', 'Profile Owner')->has('business.documents', 1));
});

it('withholds a home-based pin from staff outside its town (P8)', function () {
    $this->business->update(['is_storefront' => false]);

    $this->actingAs(registryStaff(Municipality::factory()->create()))
        ->get(route('capitol.businesses.show', $this->business))
        ->assertInertia(fn (Assert $page) => $page->where('business.location', null));

    $this->actingAs(registryStaff($this->town))
        ->get(route('capitol.businesses.show', $this->business))
        ->assertInertia(fn (Assert $page) => $page->has('business.location.lng')->has('business.location.lat'));
});

it('logs opening the edit form and a document, and never sends the full TIN to the form (P8, P9)', function () {
    $staff = registryStaff($this->town);
    Storage::disk('local')->put('registry/test/cor.pdf', '%PDF-1.4 test');
    $document = BusinessDocument::factory()->for($this->business)->create(['path' => 'registry/test/cor.pdf']);

    $this->actingAs($staff)->get(route('capitol.businesses.edit', $this->business))
        ->assertInertia(fn (Assert $page) => $page->where('form.values.tin', '')->where('form.values.tin_masked', '***-***-789-000'))
        ->assertDontSee('123-456-789');
    $this->actingAs($staff)->get(route('capitol.businesses.documents.show', ['business' => $this->business, 'document' => $document]))->assertOk();

    expect(DB::table('record_view_logs')->where(['user_id' => $staff->id, 'business_id' => $this->business->id])->count())->toBe(2);
    Storage::disk('local')->deleteDirectory('registry/test');
});
