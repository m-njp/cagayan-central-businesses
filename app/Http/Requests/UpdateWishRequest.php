<?php

namespace App\Http\Requests;

use App\Models\Wish;

final class UpdateWishRequest extends StoreWishRequest
{
    /**
     * Only the browser that made the wish may edit it. Anyone else gets a 404,
     * so wish ids cannot be probed.
     */
    public function authorize(): bool
    {
        $wish = $this->route('wish');

        abort_unless(
            $wish instanceof Wish && $this->submitterHash() !== null && hash_equals($wish->submitter_hash, $this->submitterHash()),
            404,
        );

        return true;
    }
}
