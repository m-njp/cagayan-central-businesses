<?php

namespace App\Queries;

use App\Data\FinanceUploadRow;
use App\Data\FinanceUploadTask;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Services\FinanceParsingProgress;

final class FinanceUploadQuery
{
    /** @return list<FinanceUploadRow> */
    public function listing(Municipality $town, int $year): array
    {
        return array_values(FinanceUpload::query()->where('municipality_id', $town->id)->where('fiscal_year', $year)->latest()->limit(5)
            ->get()->map(fn (FinanceUpload $upload) => $this->row($upload))->all());
    }

    public function row(FinanceUpload $upload): FinanceUploadRow
    {
        $stale = FinanceParsingProgress::isStale($upload);

        return new FinanceUploadRow($upload->public_id, $upload->original_name, (string) config("cagayan.finances.uploads.kinds.{$upload->kind}.label"),
            $stale ? 'failed' : $upload->status->value, $stale ? 'Failed' : $upload->status->label(), $upload->figures,
            $stale ? 'Parsing was interrupted. Retry parsing.' : $upload->message,
            $upload->created_at->timezone((string) config('cagayan.display_timezone'))->format('Y-m-d H:i'));
    }

    public function get(FinanceUpload $upload): FinanceUploadTask
    {
        $progress = $upload->progress ?? FinanceParsingProgress::initial('');
        if ($upload->progress === null) {
            $progress['found'] = null;
            $progress['stage'] = $upload->status->value;
        }
        $stale = FinanceParsingProgress::isStale($upload);
        $labels = FinanceLine::query()->pluck('label', 'code');
        $progress['rows'] = array_values(array_map(fn (array $row) => $row + ['label' => $labels->get($row['line'], $row['line'])], $progress['rows'] ?? []));
        $message = $upload->message;
        if ($stale) {
            $progress['failedStage'] = $progress['stage'];
            $progress['stage'] = 'failed';
            $progress['errorCode'] = 'parser_interrupted';
            $message = 'Parsing was interrupted or the worker did not start. Retry parsing.';
        }

        return new FinanceUploadTask($upload->public_id, $upload->original_name,
            $stale ? 'failed' : $upload->status->value, $upload->figures, $message, $progress,
            (int) config('cagayan.finances.uploads.poll_ms'));
    }
}
