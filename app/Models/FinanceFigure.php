<?php

namespace App\Models;

use App\Enums\FinanceBasis;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use Database\Factories\FinanceFigureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One government finance figure, parsed from a COA report or entered by hand. Public only once verified (D35).
 *
 * @property int $id
 * @property string $public_id
 * @property int $municipality_id
 * @property int $fiscal_year
 * @property string $line_code
 * @property FinanceBasis $basis
 * @property string $value
 * @property string|null $definition_variant
 * @property int|null $finance_document_id
 * @property string|null $source_url
 * @property string|null $source_label
 * @property string|null $location
 * @property list<array<string, mixed>>|null $inputs
 * @property FinanceEntryMethod $entry_method
 * @property string|null $extractor_version
 * @property FinanceFigureStatus $status
 * @property int|null $entered_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_on
 * @property string|null $note
 */
#[Fillable([
    'public_id', 'municipality_id', 'fiscal_year', 'line_code', 'basis', 'value', 'definition_variant',
    'finance_document_id', 'source_url', 'source_label', 'location', 'inputs', 'entry_method', 'extractor_version',
    'status', 'entered_by', 'reviewed_by', 'reviewed_on', 'note',
])]
class FinanceFigure extends Model
{
    /** @use HasFactory<FinanceFigureFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<FinanceDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'finance_document_id');
    }

    /**
     * The Admin who signed the figure off or rejected it. Null for figures loaded from the reviewed CSVs.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The stored COA report behind a signed-off figure, or null when it is pending, entered by hand, not on disk, or
     * a Capitol upload, whose file stays private (D42).
     */
    public function publicFile(): ?string
    {
        $file = $this->status === FinanceFigureStatus::Verified ? $this->document?->file : null;
        if ($file === null || str_starts_with($file, FinanceUpload::FOLDER.'/')) {
            return null;
        }
        $root = realpath(base_path('data/towns'));
        $path = $root === false ? false : realpath("$root/$file");

        return $path !== false && str_starts_with($path, $root.DIRECTORY_SEPARATOR) ? $path : null;
    }

    /**
     * Only each contributing cell and its amount, so nothing else a parser emits reaches a page.
     *
     * @return list<array{location: string, value: string}>|null
     */
    public static function cleanInputs(mixed $inputs): ?array
    {
        $clean = [];
        foreach (is_array($inputs) ? $inputs : [] as $input) {
            $location = is_array($input) ? ($input['location'] ?? null) : null;
            $value = is_array($input) ? ($input['value'] ?? null) : null;
            if (is_string($location) && is_string($value) && is_numeric($value)) {
                $clean[] = ['location' => mb_substr($location, 0, (int) config('cagayan.finances.location_max')), 'value' => $value];
            }
        }

        return $clean === [] ? null : $clean;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'basis' => FinanceBasis::class,
            'value' => 'decimal:2',
            'entry_method' => FinanceEntryMethod::class,
            'status' => FinanceFigureStatus::class,
            'reviewed_on' => 'date',
            'inputs' => 'array',
        ];
    }
}
