<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** P9: one insert-only row per full record open, written before the response. */
final class LogRecordView
{
    public function handle(User $user, Business $business): void
    {
        DB::table('record_view_logs')->insert(['user_id' => $user->id, 'business_id' => $business->id, 'viewed_at' => now()]);
    }
}
