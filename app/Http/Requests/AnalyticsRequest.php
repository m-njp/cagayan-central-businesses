<?php

namespace App\Http\Requests;

final class AnalyticsRequest extends AnalyticsFiltersRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capitol') ?? false;
    }
}
