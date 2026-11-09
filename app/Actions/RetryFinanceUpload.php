<?php

namespace App\Actions;

use App\Enums\FinanceUploadStatus;
use App\Jobs\ExtractFinanceUpload;
use App\Models\FinanceUpload;
use App\Services\FinanceParsingProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RetryFinanceUpload
{
    public function handle(FinanceUpload $upload): void
    {
        DB::transaction(function () use ($upload): void {
            $current = FinanceUpload::query()->lockForUpdate()->findOrFail($upload->id);
            abort_unless(in_array($current->status, [FinanceUploadStatus::Failed, FinanceUploadStatus::Done], true) || $current->isStale(), 409, 'This report is already queued or running.');
            $attempt = (string) Str::uuid();
            $current->update(['status' => FinanceUploadStatus::Queued, 'message' => null, 'issues' => null, 'figures' => 0,
                'progress' => FinanceParsingProgress::initial($attempt)]);
            ExtractFinanceUpload::dispatch($current->id, $attempt)->afterCommit();
        });
    }
}
