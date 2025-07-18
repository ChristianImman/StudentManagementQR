<?php

namespace PhpOffice\PhpSpreadsheet\Chart\Renderer;

use PhpOffice\PhpSpreadsheet\Chart\Chart;

interface IRenderer
{
    
    public function __construct(Chart $chart);

    
    public function render(?string $filename): bool;
}
