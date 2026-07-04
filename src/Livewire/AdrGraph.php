<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Services\RelationGraph;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Server-rendered SVG of the supersede graph, laid out git-log style: one
 * record per row in sequence order, arcs on the left connecting superseding
 * records to what they replace.
 */
#[Layout('adr-manager::layouts.app')]
final class AdrGraph extends Component
{
    private const int ROW_HEIGHT = 56;

    private const int SPINE_X = 64;

    public function render(RelationGraph $graph): View
    {
        $data = $graph->build();

        $nodes = [];

        foreach ($data['nodes'] as $index => $node) {
            $nodes[] = [...$node, 'y' => self::ROW_HEIGHT / 2 + $index * self::ROW_HEIGHT];
        }

        $yById = array_column($nodes, 'y', 'id');

        $edges = [];

        foreach ($data['edges'] as $edge) {
            if (! isset($yById[$edge['from']], $yById[$edge['to']])) {
                continue; // The index row for one endpoint is gone; skip the arc.
            }

            $span = (int) abs($yById[$edge['from']] - $yById[$edge['to']]) / self::ROW_HEIGHT;

            $edges[] = [
                'fromY' => $yById[$edge['from']],
                'toY' => $yById[$edge['to']],
                'depth' => 18 + 10 * min($span, 4),
            ];
        }

        return view()->make('adr-manager::livewire.adr-graph', [
            'nodes' => $nodes,
            'edges' => $edges,
            'spineX' => self::SPINE_X,
            'height' => max(count($nodes), 1) * self::ROW_HEIGHT,
        ]);
    }
}
