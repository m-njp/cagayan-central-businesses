<?php

namespace App\Services\Privacy;

use App\Data\GuardedFigure;
use InvalidArgumentException;

/**
 * The only place that decides whether a sales figure may be shown (D8, privacy rules P2 to P7).
 */
final class PrivacyGuard
{
    /** P2 and P3 for one group of businesses. */
    public function figure(?string $total, int $count, ?string $maxSingle): GuardedFigure
    {
        if ($count < (int) config('cagayan.privacy.small_group_min')) {
            return GuardedFigure::hidden('small_group');
        }

        $total = $this->decimal($total ?? '0');
        $share = $this->decimal((string) config('cagayan.privacy.dominance_max_share'));
        if (bccomp($total, '0', 2) > 0 && $maxSingle !== null && bccomp(bcdiv($this->decimal($maxSingle), $total, 6), $share, 6) > 0) {
            return GuardedFigure::hidden('dominance');
        }

        return new GuardedFigure('shown', value: $total);
    }

    /**
     * P4: when exactly one cell of a breakdown is hidden, the smallest shown cell is hidden too,
     * so the hidden one cannot be worked out from the total.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, array{total: ?string, count: int, maxSingle: ?string}>  $cells
     * @return array<TKey, GuardedFigure>
     */
    public function breakdown(array $cells): array
    {
        $figures = array_map(fn (array $cell) => $this->figure($cell['total'], $cell['count'], $cell['maxSingle']), $cells);
        $shown = array_filter($figures, fn (GuardedFigure $figure) => ! $figure->isHidden());

        if (count($figures) - count($shown) === 1 && $shown !== []) {
            uasort($shown, fn (GuardedFigure $a, GuardedFigure $b) => bccomp($this->decimal($a->value), $this->decimal($b->value), 2));
            $figures[array_key_first($shown)] = GuardedFigure::hidden('complementary');
        }

        return $figures;
    }

    /** P6: a value derived from any hidden figure is hidden with the first hidden reason. */
    public function derived(GuardedFigure ...$inputs): ?GuardedFigure
    {
        foreach ($inputs as $input) {
            if ($input->isHidden()) {
                return GuardedFigure::hidden((string) $input->reason);
            }
        }

        return null;
    }

    /** P7 and M15: the public sees the band containing a shown total; hidden stays hidden. */
    public function band(GuardedFigure $figure): GuardedFigure
    {
        if ($figure->isHidden()) {
            return $figure;
        }

        /** @var list<int> $edges */
        $edges = config('cagayan.privacy.sales_bands');
        $value = $this->decimal($figure->value);
        $upper = null;
        foreach ($edges as $edge) {
            if (bccomp($value, (string) $edge, 2) < 0) {
                $upper = $edge;
                break;
            }
            $lower = $edge;
        }

        $label = match (true) {
            ! isset($lower) => 'Under '.$this->pesos($edges[0]),
            $upper === null => 'Over '.$this->pesos($lower),
            default => $this->pesos($lower).' to '.$this->pesos($upper),
        };

        return new GuardedFigure('shown', band: $label);
    }

    /**
     * Sales arrive from SQL as decimal strings; anything else is a programming error, never a figure to show.
     *
     * @return numeric-string
     */
    private function decimal(?string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Sales figures must be decimal strings.');
        }

        return $value;
    }

    private function pesos(int $amount): string
    {
        return $amount >= 1_000_000_000
            ? '₱'.($amount / 1_000_000_000).' billion'
            : '₱'.($amount / 1_000_000).' million';
    }
}
