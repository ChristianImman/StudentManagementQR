<?php

namespace PhpOffice\PhpSpreadsheet\Chart\Renderer;


class JpGraph extends JpGraphRendererBase
{
    protected static function init(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        
        
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('bar');
        \JpGraph\JpGraph::module('contour');
        \JpGraph\JpGraph::module('line');
        \JpGraph\JpGraph::module('pie');
        \JpGraph\JpGraph::module('pie3d');
        \JpGraph\JpGraph::module('radar');
        \JpGraph\JpGraph::module('regstat');
        \JpGraph\JpGraph::module('scatter');
        \JpGraph\JpGraph::module('stock');

        $loaded = true;
    }
}
