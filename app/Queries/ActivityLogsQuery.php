<?php

namespace App\Queries;

use App\Data\Pagination;
use App\Data\SelectOption;
use App\Data\WorkspaceTable;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

final class ActivityLogsQuery
{
    public function get(?string $event): WorkspaceTable
    {
        $results = DB::table('activity_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.actor_id')
            ->when($event, fn ($q) => $q->where('a.event', $event))
            ->orderByDesc('a.occurred_at')->orderByDesc('a.id')
            ->paginate(20, ['a.id', 'a.event', 'a.occurred_at', 'u.name'])->withQueryString();

        return new WorkspaceTable(
            array_values(array_map(fn ($r) => ['id' => (string) $r->id, 'event' => ActivityLog::EVENTS[$r->event] ?? 'Account Event',
                'occurredAt' => (string) $r->occurred_at, 'actor' => is_string($r->name) ? $r->name : 'Unknown Account'], $results->items())),
            Pagination::from($results),
            categories: array_map(fn ($value, $label) => new SelectOption($value, $label), array_keys(ActivityLog::EVENTS), array_values(ActivityLog::EVENTS)),
            filters: ['event' => $event],
        );
    }
}
