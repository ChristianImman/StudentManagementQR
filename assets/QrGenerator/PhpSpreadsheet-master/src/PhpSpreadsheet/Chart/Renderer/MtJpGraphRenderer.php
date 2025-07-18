<?php

namespace PhpOffice\PhpSpreadsheet\Chart\Renderer;

use mitoteam\jpgraph\MtJpGraph;


class MtJpGraphRenderer extends JpGraphRendererBase
{
    protected static function init(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        MtJpGraph::load([
            'bar',
            'contour',
            'line',
            'pie',
            'pie3d',
            'radar',
            'regstat',
            'scatter',
            'stock',
        ], true); 

        $loaded = true;
    }
}
