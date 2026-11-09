<?php

namespace App\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class Pagination
{
    /** @param list<array{page: int, url: string}> $links */
    public function __construct(
        public int $page, public int $lastPage, public int $total,
        public ?int $from, public ?int $to, public ?string $previous, public ?string $next,
        public array $links,
    ) {}

    /** @param LengthAwarePaginator<int, mixed> $results */
    public static function from(LengthAwarePaginator $results): self
    {
        $current = min($results->lastPage(), $results->currentPage());
        $pages = array_values(array_unique([1, ...range(max(1, $current - 1), min($results->lastPage(), $current + 1)), $results->lastPage()]));

        return new self(
            $results->currentPage(), $results->lastPage(), $results->total(),
            $results->firstItem(), $results->lastItem(), $results->previousPageUrl(), $results->nextPageUrl(),
            array_map(fn (int $page) => ['page' => $page, 'url' => $results->url($page)], $pages),
        );
    }
}
