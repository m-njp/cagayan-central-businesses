<?php

namespace Database\Factories;

use App\Enums\FinanceBasis;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinanceFigure>
 */
class FinanceFigureFactory extends Factory
{
    /**
     * Define the model's default state. A pending figure parsed from its own report.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'municipality_id' => Municipality::factory(),
            'fiscal_year' => 2024,
            'line_code' => fn () => self::lineCode('performance.revenue'),
            'basis' => FinanceBasis::Actual,
            'value' => fake()->numberBetween(50_000_000, 900_000_000).'.00',
            'finance_document_id' => function (array $attributes) {
                $report = ['municipality_id' => $attributes['municipality_id'], 'fiscal_year' => $attributes['fiscal_year'], 'kind' => 'executive_summary'];

                return (FinanceDocument::query()->where($report)->first() ?? FinanceDocument::factory()->create($report))->id;
            },
            'entry_method' => FinanceEntryMethod::Parsed,
            'status' => FinanceFigureStatus::Pending,
        ];
    }

    public function line(string $code, int $year, string $value): static
    {
        return $this->state(fn () => ['line_code' => self::lineCode($code), 'fiscal_year' => $year, 'value' => $value]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => FinanceFigureStatus::Verified, 'reviewed_on' => '2026-10-28']);
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'entry_method' => FinanceEntryMethod::Manual,
            'finance_document_id' => null,
            'source_url' => 'https://www.coa.gov.ph/reports/annual-audit-reports/',
        ]);
    }

    private static function lineCode(string $code): string
    {
        return (FinanceLine::query()->where('code', $code)->first() ?? FinanceLine::factory()->create(['code' => $code]))->code;
    }
}
