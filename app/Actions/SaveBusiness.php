<?php

namespace App\Actions;

use App\Enums\RecordStatus;
use App\Models\ActivityLog;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SaveBusiness
{
    /**
     * Saves a draft or submits for review. Any save of an approved record takes it out of public view until approved again (D40).
     *
     * @param  array<string, mixed>  $input  Validated SaveBusinessRequest input
     */
    public function handle(User $staff, ?Business $business, array $input): Business
    {
        return DB::transaction(function () use ($staff, $business, $input): Business {
            $submit = (bool) $input['submit'];
            $business ??= new Business(['public_id' => (string) Str::uuid(), 'is_simulated' => false]);
            $business->fill([
                ...collect($input)->only([
                    'business_name', 'description', 'year_established', 'is_storefront', 'street_address', 'business_phone',
                    'business_email', 'owner_name', 'contact_number', 'ownership_type', 'size_category', 'employee_count',
                    'registration_body', 'registration_number', 'registration_valid_until', 'rdo_code',
                ])->all(),
                'tin' => ($input['tin'] ?? null) ?: $business->tin,
                'business_type_id' => BusinessType::query()->where('code', $input['business_type'])->value('id'),
                'barangay_id' => Barangay::query()->where('psgc_code', $input['barangay'])->value('id'),
                // A placeholder until the bound update below; the column is not null.
                'location' => DB::raw('ST_SetSRID(ST_MakePoint(0, 0), 4326)'),
            ])->forceFill([
                'record_status' => $submit ? RecordStatus::Submitted : RecordStatus::Draft,
                'submitted_by' => $staff->id,
                'submitted_at' => $submit ? now() : null,
                'review_note' => $submit ? null : $business->review_note,
                'bir_verified_on' => null,
            ])->save();
            DB::update('update businesses set location = ST_SetSRID(ST_MakePoint(?, ?), 4326) where id = ?', [(float) $input['lng'], (float) $input['lat'], $business->id]);

            if (! empty($input['permit_number'])) {
                $business->permits()->updateOrCreate(
                    ['year' => (int) substr((string) $input['permit_valid_from'], 0, 4)],
                    ['permit_number' => $input['permit_number'], 'valid_from' => $input['permit_valid_from'], 'valid_until' => $input['permit_valid_until']],
                );
            }

            foreach ($input['documents'] ?? [] as $document) {
                /** @var UploadedFile $file */
                $file = $document['file'];
                $business->documents()->create([
                    'public_id' => (string) Str::uuid(),
                    'kind' => $document['kind'],
                    'path' => $file->store("registry/{$business->public_id}", 'local'),
                    'original_name' => Str::limit($file->getClientOriginalName(), 120, ''),
                    'mime' => (string) $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'issued_on' => $document['issued_on'] ?? null,
                    'expires_on' => $document['expires_on'] ?? null,
                    'uploaded_by' => $staff->id,
                ]);
            }

            ActivityLog::record($submit ? 'business_submitted' : 'business_saved', $staff->id);

            return $business;
        });
    }
}
