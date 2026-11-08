<?php

namespace Database\Factories;

use App\Enums\FinanceUploadStatus;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Services\FinanceParsingProgress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinanceUpload>
 */
class FinanceUploadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publicId = (string) Str::uuid();

        return [
            'public_id' => $publicId,
            'municipality_id' => Municipality::factory(),
            'fiscal_year' => 2025,
            'kind' => 'fs_workbook',
            'path' => FinanceUpload::FOLDER."/{$publicId}.xlsx",
            'original_name' => 'FS-2025.xlsx',
            'sha256' => hash('sha256', $publicId),
            'size' => 1024,
            'status' => FinanceUploadStatus::Queued,
            'progress' => FinanceParsingProgress::initial((string) Str::uuid()),
        ];
    }
}
