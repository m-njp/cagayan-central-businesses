<?php

namespace App\Queries;

use App\Data\CommunityWishSnapshot;
use App\Models\Municipality;
use Illuminate\Filesystem\Filesystem;

/** The curated sample wishes for the demo town (data/economic-performance/demo.php), shared by Home and Economy. */
final class SampleWishesQuery
{
    public function __construct(private Filesystem $files) {}

    public function get(): ?CommunityWishSnapshot
    {
        $town = Municipality::query()->where('psgc_code', config('cagayan.performance.demo_town'))->first(['psgc_code', 'name']);
        if ($town === null) {
            return null;
        }

        /** @var array{asOf: string, period: string, source: string, types: list<array{id: string, label: string, count: int}>, specific: list<array{id: string, label: string, count: int}>} $data */
        $data = $this->files->getRequire(base_path('data/economic-performance/demo.php'))['wishes'];
        $items = [];
        foreach (['types', 'specific'] as $group) {
            foreach ($data[$group] as $item) {
                $items[] = ['id' => $group.':'.$item['id'], 'label' => $item['label'], 'count' => $item['count']];
            }
        }
        usort($items, fn (array $a, array $b) => ($b['count'] <=> $a['count']) ?: strcmp($a['label'], $b['label']) ?: strcmp($a['id'], $b['id']));

        return new CommunityWishSnapshot($town->psgc_code, $town->name, $data['asOf'], $data['period'], $data['source'], $items);
    }
}
