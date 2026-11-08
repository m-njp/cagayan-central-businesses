<?php

namespace App\Models;

use App\Enums\FinanceUploadStatus;
use Database\Factories\FinanceUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A COA report uploaded on Capitol Finances and read by the parser on the queue. Its figures enter the ledger as
 * pending, so someone other than the uploader signs them off (D35). The file stays on the private disk.
 *
 * @property int $id
 * @property string $public_id
 * @property int $municipality_id
 * @property int $fiscal_year
 * @property string $kind
 * @property string $path
 * @property string $original_name
 * @property string $sha256
 * @property int $size
 * @property FinanceUploadStatus $status
 * @property int $figures
 * @property list<array<string, mixed>>|null $issues
 * @property string|null $message
 * @property int|null $uploaded_by
 * @property Carbon $created_at
 * @property array<string, mixed>|null $progress
 * @property Carbon $updated_at
 */
#[Fillable([
    'public_id', 'municipality_id', 'fiscal_year', 'kind', 'path', 'original_name', 'sha256', 'size',
    'status', 'figures', 'issues', 'message', 'uploaded_by', 'progress',
])]
class FinanceUpload extends Model
{
    /** @use HasFactory<FinanceUploadFactory> */
    use HasFactory;

    /** The private-disk folder for uploaded reports; finance documents read from here cite it in `file`. */
    public const string FOLDER = 'finance-uploads';

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'size' => 'integer',
            'figures' => 'integer',
            'status' => FinanceUploadStatus::class,
            'issues' => 'array',
            'progress' => 'array',
        ];
    }
}
