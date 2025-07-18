<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface IReader
{
    
    public const LOAD_WITH_CHARTS = 1;

    
    public const READ_DATA_ONLY = 2;

    
    public const IGNORE_EMPTY_CELLS = 4;

    
    public const IGNORE_ROWS_WITH_NO_CELLS = 8;

    public function __construct();

    
    public function canRead(string $filename): bool;

    
    public function getReadDataOnly(): bool;

    
    public function setReadDataOnly(bool $readDataOnly): self;

    
    public function getReadEmptyCells(): bool;

    
    public function setReadEmptyCells(bool $readEmptyCells): self;

    
    public function getIncludeCharts(): bool;

    
    public function setIncludeCharts(bool $includeCharts): self;

    
    public function getLoadSheetsOnly(): ?array;

    
    public function setLoadSheetsOnly(string|array|null $value): self;

    
    public function setLoadAllSheets(): self;

    
    public function getReadFilter(): IReadFilter;

    
    public function setReadFilter(IReadFilter $readFilter): self;

    
    public function load(string $filename, int $flags = 0): Spreadsheet;
}
