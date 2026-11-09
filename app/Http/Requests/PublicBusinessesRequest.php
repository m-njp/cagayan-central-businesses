<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class PublicBusinessesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unknown codes are ignored rather than rejected, so an old shared link still opens.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return ['town' => ['nullable', 'string', 'max:10']];
    }

    /** A town with barangay boundaries, or null until one is chosen. */
    public function town(): ?string
    {
        $code = $this->string('town')->toString();

        return $code !== '' && DB::table('barangays as g')->join('municipalities as m', 'm.id', '=', 'g.municipality_id')
            ->where('m.psgc_code', $code)->whereNotNull('g.boundary')->exists() ? $code : null;
    }
}
