<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class BaseReader implements IReader
{
    
    protected bool $readDataOnly = false;

    
    protected bool $readEmptyCells = true;

    
    protected bool $includeCharts = false;

    
    protected ?array $loadSheetsOnly = null;

    
    protected bool $ignoreRowsWithNoCells = false;

    
    protected IReadFilter $readFilter;

    
    protected $fileHandle;

    protected ?XmlScanner $securityScanner = null;

    protected ?IValueBinder $valueBinder = null;

    public function __construct()
    {
        $this->readFilter = new DefaultReadFilter();
    }

    public function getReadDataOnly(): bool
    {
        return $this->readDataOnly;
    }

    public function setReadDataOnly(bool $readCellValuesOnly): self
    {
        $this->readDataOnly = $readCellValuesOnly;

        return $this;
    }

    public function getReadEmptyCells(): bool
    {
        return $this->readEmptyCells;
    }

    public function setReadEmptyCells(bool $readEmptyCells): self
    {
        $this->readEmptyCells = $readEmptyCells;

        return $this;
    }

    public function getIgnoreRowsWithNoCells(): bool
    {
        return $this->ignoreRowsWithNoCells;
    }

    public function setIgnoreRowsWithNoCells(bool $ignoreRowsWithNoCells): self
    {
        $this->ignoreRowsWithNoCells = $ignoreRowsWithNoCells;

        return $this;
    }

    public function getIncludeCharts(): bool
    {
        return $this->includeCharts;
    }

    public function setIncludeCharts(bool $includeCharts): self
    {
        $this->includeCharts = $includeCharts;

        return $this;
    }

    
    public function getLoadSheetsOnly(): ?array
    {
        return $this->loadSheetsOnly;
    }

    
    public function setLoadSheetsOnly(string|array|null $sheetList): self
    {
        if ($sheetList === null) {
            return $this->setLoadAllSheets();
        }

        $this->loadSheetsOnly = is_array($sheetList) ? $sheetList : [$sheetList];

        return $this;
    }

    public function setLoadAllSheets(): self
    {
        $this->loadSheetsOnly = null;

        return $this;
    }

    public function getReadFilter(): IReadFilter
    {
        return $this->readFilter;
    }

    public function setReadFilter(IReadFilter $readFilter): self
    {
        $this->readFilter = $readFilter;

        return $this;
    }

    public function getSecurityScanner(): ?XmlScanner
    {
        return $this->securityScanner;
    }

    public function getSecurityScannerOrThrow(): XmlScanner
    {
        if ($this->securityScanner === null) {
            throw new ReaderException('Security scanner is unexpectedly null');
        }

        return $this->securityScanner;
    }

    protected function processFlags(int $flags): void
    {
        if (((bool) ($flags & self::LOAD_WITH_CHARTS)) === true) {
            $this->setIncludeCharts(true);
        }
        if (((bool) ($flags & self::READ_DATA_ONLY)) === true) {
            $this->setReadDataOnly(true);
        }
        if (((bool) ($flags & self::IGNORE_EMPTY_CELLS)) === true) {
            $this->setReadEmptyCells(false);
        }
        if (((bool) ($flags & self::IGNORE_ROWS_WITH_NO_CELLS)) === true) {
            $this->setIgnoreRowsWithNoCells(true);
        }
    }

    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        throw new PhpSpreadsheetException('Reader classes must implement their own loadSpreadsheetFromFile() method');
    }

    
    public function load(string $filename, int $flags = 0): Spreadsheet
    {
        $this->processFlags($flags);

        try {
            return $this->loadSpreadsheetFromFile($filename);
        } catch (ReaderException $e) {
            throw $e;
        }
    }

    
    protected function openFile(string $filename): void
    {
        $fileHandle = false;
        if ($filename) {
            File::assertFile($filename);

            
            $fileHandle = fopen($filename, 'rb');
        }
        if ($fileHandle === false) {
            throw new ReaderException('Could not open file ' . $filename . ' for reading.');
        }

        $this->fileHandle = $fileHandle;
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        throw new PhpSpreadsheetException('Reader classes must implement their own listWorksheetInfo() method');
    }

    
    public function listWorksheetNames(string $filename): array
    {
        $returnArray = [];
        $info = $this->listWorksheetInfo($filename);
        foreach ($info as $infoArray) {
            $returnArray[] = $infoArray['worksheetName'];
        }

        return $returnArray;
    }

    public function getValueBinder(): ?IValueBinder
    {
        return $this->valueBinder;
    }

    public function setValueBinder(?IValueBinder $valueBinder): self
    {
        $this->valueBinder = $valueBinder;

        return $this;
    }

    protected function newSpreadsheet(): Spreadsheet
    {
        return new Spreadsheet();
    }
}
