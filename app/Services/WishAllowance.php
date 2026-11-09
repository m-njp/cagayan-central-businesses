<?php

namespace App\Services;

use App\Models\Wish;
use App\Models\WishEdit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The cooldown and edit limits of one anonymous submitter (privacy-rules.md, P12).
 * The wish page and both wish actions read them here, so what the page says
 * and what the server enforces cannot disagree.
 */
final class WishAllowance
{
    /**
     * When this submitter may add another wish, or null when they may now.
     */
    public function nextWishAt(string $submitterHash): ?CarbonImmutable
    {
        $latest = Wish::query()->where('submitter_hash', $submitterHash)->max('created_at');

        if ($latest === null) {
            return null;
        }

        $next = CarbonImmutable::parse($latest)->addDays((int) config('cagayan.wishes.cooldown_days'));

        return $next->isFuture() ? $next : null;
    }

    /**
     * Whether this network may still bring in a respondent who has never submitted.
     * The network hash already changes every day, so this counts today only.
     */
    public function networkAcceptsNewRespondent(string $networkHash): bool
    {
        $respondentsToday = Wish::query()
            ->where('network_hash', $networkHash)
            ->distinct()
            ->count('submitter_hash');

        return $respondentsToday < (int) config('cagayan.wishes.new_respondents_per_network_per_day');
    }

    public function editsLeft(string $submitterHash): int
    {
        return max(0, (int) config('cagayan.wishes.edits_per_window') - $this->recentEdits($submitterHash)->count());
    }

    /**
     * When the oldest edit leaves the window, or null while edits are left.
     */
    public function nextEditAt(string $submitterHash): ?CarbonImmutable
    {
        if ($this->editsLeft($submitterHash) > 0) {
            return null;
        }

        $oldest = $this->recentEdits($submitterHash)->min('edited_at');

        return CarbonImmutable::parse($oldest)->addDays((int) config('cagayan.wishes.edit_window_days'));
    }

    /**
     * A date for a message to the resident, such as "October 6 at 4:12 PM".
     */
    public function forDisplay(CarbonImmutable $at): string
    {
        return $at->setTimezone((string) config('cagayan.display_timezone'))->format('F j \a\t g:i A');
    }

    /**
     * @return Builder<WishEdit>
     */
    private function recentEdits(string $submitterHash): Builder
    {
        return WishEdit::query()
            ->whereIn('wish_id', Wish::query()->select('id')->where('submitter_hash', $submitterHash))
            ->where('edited_at', '>', now()->subDays((int) config('cagayan.wishes.edit_window_days')));
    }
}
