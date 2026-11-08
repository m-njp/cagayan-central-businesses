<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/** One COA report for the town and report year; Staff only for their assigned towns (edit-town). */
final class UploadFinanceReportRequest extends FinanceReportRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $kinds = (array) config('cagayan.finances.uploads.kinds');
        $kind = $this->input('kind');
        $extensions = is_string($kind) ? ($kinds[$kind]['extensions'] ?? []) : [];
        // An .xlsx is a ZIP inside, so a workbook may be detected as either.
        $mimes = in_array('xlsx', $extensions, true) ? ['xlsx', 'zip'] : $extensions;

        return [
            ...parent::rules(),
            'kind' => ['required', 'string', Rule::in(array_keys($kinds))],
            'file' => ['required', 'file', 'max:'.config('cagayan.finances.uploads.file_max_kb'),
                'extensions:'.implode(',', $extensions ?: ['pdf']), 'mimes:'.implode(',', $mimes ?: ['pdf'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.extensions' => 'Choose a file of the kind selected: a PDF Executive Summary, an XLSX workbook, or a ZIP of workbooks.',
            'file.mimes' => 'That file does not look like the kind selected.',
        ];
    }

    public function report(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('file');
    }
}
