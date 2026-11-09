<?php

namespace App\Actions;

use App\Enums\DocumentKind;
use App\Enums\RecordStatus;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ReviewBusiness
{
    /** Approval publishes the record and marks the BIR badge when a COR is on file (D40); a return needs a note. */
    public function handle(User $reviewer, Business $business, RecordStatus $decision, ?string $note): void
    {
        DB::transaction(function () use ($reviewer, $business, $decision, $note): void {
            $approved = $decision === RecordStatus::Approved;
            $business->forceFill([
                'record_status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $approved ? null : $note,
                'bir_verified_on' => $approved && $business->documents()->where('kind', DocumentKind::BirCor)->exists() ? now()->toDateString() : null,
            ])->save();
            ActivityLog::record($approved ? 'business_approved' : 'business_returned', $reviewer->id);
        });
    }
}
