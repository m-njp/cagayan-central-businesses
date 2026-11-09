<?php

namespace Database\Factories;

use App\Enums\DocumentKind;
use App\Models\Business;
use App\Models\BusinessDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BusinessDocument>
 */
class BusinessDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publicId = (string) Str::uuid();

        return [
            'public_id' => $publicId,
            'business_id' => Business::factory(),
            'kind' => DocumentKind::BirCor,
            'path' => "registry/{$publicId}.pdf",
            'original_name' => 'certificate-of-registration.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'sha256' => hash('sha256', $publicId),
        ];
    }

    public function kind(DocumentKind $kind): static
    {
        return $this->state(fn () => ['kind' => $kind]);
    }
}
