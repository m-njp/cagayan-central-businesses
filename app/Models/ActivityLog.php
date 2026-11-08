<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Only event codes and actor keys belong in this append-only stream. */
final class ActivityLog
{
    public const array EVENTS = [
        'signed_in' => 'Signed In', 'signed_out' => 'Signed Out',
        'sign_in_failed' => 'Sign In Failed', 'session_expired' => 'Session Ended',
        'profile_updated' => 'Profile Updated', 'role_assigned_from_console' => 'Role Assigned from Console',
        'finance_figure_entered' => 'Finance Figure Entered', 'finance_report_uploaded' => 'Finance Report Uploaded', 'finance_report_read' => 'Finance Report Read', 'population_entered' => 'Population Entered', 'population_verified' => 'Population Verified', 'population_returned' => 'Population Returned', 'finance_figures_signed_off' => 'Finance Figures Signed Off',
        'finance_figures_rejected' => 'Finance Figures Rejected',
        'business_category_created' => 'Business Category Created', 'business_category_updated' => 'Business Category Updated',
        'business_type_created' => 'Business Type Created', 'business_type_updated' => 'Business Type Updated',
        'business_saved' => 'Business Saved', 'business_submitted' => 'Business Submitted',
        'business_approved' => 'Business Approved', 'business_returned' => 'Business Returned',
        // Recorded before the Category model replaced Focus Tags (D39).
        'focus_tag_created' => 'Focus Tag Created', 'focus_tag_updated' => 'Focus Tag Updated',
    ];

    public static function record(string $event, ?int $actorId): void
    {
        DB::table('activity_logs')->insert([
            'id' => (string) Str::uuid(),
            'actor_id' => $actorId,
            'event' => $event,
            'occurred_at' => now(),
        ]);
    }
}
