<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Database\Factories\BusinessDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An uploaded registry document on the private disk. Admin class: served only through the authorized document route.
 *
 * @property int $id
 * @property string $public_id
 * @property int $business_id
 * @property DocumentKind $kind
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property string $sha256
 * @property Carbon|null $issued_on
 * @property Carbon|null $expires_on
 * @property int|null $uploaded_by
 */
#[Fillable([
    'public_id', 'business_id', 'kind', 'path', 'original_name', 'mime', 'size', 'sha256', 'issued_on', 'expires_on', 'uploaded_by',
])]
#[Hidden(['path'])]
class BusinessDocument extends Model
{
    /** @use HasFactory<BusinessDocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** The stored file, or null when it is missing or outside the private registry folder. */
    public function file(): ?string
    {
        $root = realpath(storage_path('app/private/registry'));
        $path = $root === false ? false : realpath("{$root}/".substr($this->path, strlen('registry/')));

        return $path !== false && str_starts_with($path, $root.DIRECTORY_SEPARATOR) ? $path : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => DocumentKind::class, 'size' => 'integer', 'issued_on' => 'date', 'expires_on' => 'date'];
    }
}
