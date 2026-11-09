<?php

namespace Database\Factories;

use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Models\FinanceDocument;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceDocument>
 */
class FinanceDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'fiscal_year' => 2024,
            'kind' => FinanceDocumentKind::ExecutiveSummary,
            'availability' => FinanceAvailability::Available,
            'file' => 'executive-summaries/2024/Town/Town-Executive-Summary-2024.pdf',
            'source_url' => 'https://www.coa.gov.ph/download/'.fake()->unique()->numberBetween(1000, 99999).'/summary.pdf',
            'sha256' => hash('sha256', fake()->unique()->uuid()),
            'checked_on' => '2026-10-27',
        ];
    }

    public function notPublished(): static
    {
        return $this->state(fn () => ['availability' => FinanceAvailability::NotPublished, 'file' => null, 'source_url' => null, 'sha256' => null]);
    }
}
