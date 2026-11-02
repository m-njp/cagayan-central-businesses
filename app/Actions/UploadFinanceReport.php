<?php

namespace App\Actions;

use App\Enums\FinanceUploadStatus;
use App\Jobs\ExtractFinanceUpload;
use App\Models\ActivityLog;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keeps the uploaded report on the private disk and queues it to be read. The same file for the same town, year and
 * kind is read once; uploading it again returns the earlier upload.
 */
final class UploadFinanceReport
{
    /** @return array{upload: FinanceUpload, repeated: bool} */
    public function handle(User $user, Municipality $town, int $reportYear, string $kind, UploadedFile $file): array
    {
        $sha256 = (string) hash_file('sha256', $file->getRealPath());
        $earlier = FinanceUpload::query()->where('municipality_id', $town->id)->where('fiscal_year', $reportYear)
            ->where('kind', $kind)->where('sha256', $sha256)->first();
        if ($earlier !== null) {
            return ['upload' => $earlier, 'repeated' => true];
        }

        $publicId = (string) Str::uuid();
        $path = $file->storeAs(FinanceUpload::FOLDER, $publicId.'.'.strtolower($file->getClientOriginalExtension()), 'local');
        $upload = DB::transaction(function () use ($user, $town, $reportYear, $kind, $file, $sha256, $publicId, $path): FinanceUpload {
            $upload = FinanceUpload::query()->create([
                'public_id' => $publicId, 'municipality_id' => $town->id, 'fiscal_year' => $reportYear, 'kind' => $kind,
                'path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 255), 'sha256' => $sha256,
                'size' => $file->getSize(), 'status' => FinanceUploadStatus::Queued, 'uploaded_by' => $user->id,
            ]);
            ActivityLog::record('finance_report_uploaded', $user->id);

            return $upload;
        });
        ExtractFinanceUpload::dispatch($upload->id)->afterCommit();

        return ['upload' => $upload, 'repeated' => false];
    }
}
