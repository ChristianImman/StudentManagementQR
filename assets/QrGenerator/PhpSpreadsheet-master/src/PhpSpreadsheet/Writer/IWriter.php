<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface IWriter
{
    public const SAVE_WITH_CHARTS = 1;

    public const DISABLE_PRECALCULATE_FORMULAE = 2;

    
    public function __construct(Spreadsheet $spreadsheet);

    
    public function getIncludeCharts(): bool;

    
    public function setIncludeCharts(bool $includeCharts): self;

    
    public function getPreCalculateFormulas(): bool;

    
    public function setPreCalculateFormulas(bool $precalculateFormulas): self;

    
    public function save($filename, int $flags = 0): void;

    
    public function getUseDiskCaching(): bool;

    
    public function setUseDiskCaching(bool $useDiskCache, ?string $cacheDirectory = null): self;

    
    public function getDiskCachingDirectory(): string;
}
