<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;

class TableReader
{
    private Worksheet $worksheet;

    private SimpleXMLElement $tableXml;

    
    private $tableAttributes;

    public function __construct(Worksheet $workSheet, SimpleXMLElement $tableXml)
    {
        $this->worksheet = $workSheet;
        $this->tableXml = $tableXml;
    }

    
    public function load(array $tableStyles, array $dxfs): void
    {
        $this->tableAttributes = $this->tableXml->attributes() ?? [];
        
        $tableRange = (string) preg_replace('/\$/', '', $this->tableAttributes['ref'] ?? '');
        if (str_contains($tableRange, ':')) {
            $this->readTable($tableRange, $tableStyles, $dxfs);
        }
    }

    
    private function readTable(string $tableRange, array $tableStyles, array $dxfs): void
    {
        $table = new Table($tableRange);
        $table->setName((string) ($this->tableAttributes['displayName'] ?? ''));
        $table->setShowHeaderRow(((string) ($this->tableAttributes['headerRowCount'] ?? '')) !== '0');
        $table->setShowTotalsRow(((string) ($this->tableAttributes['totalsRowCount'] ?? '')) === '1');

        $this->readTableAutoFilter($table, $this->tableXml->autoFilter);
        $this->readTableColumns($table, $this->tableXml->tableColumns);
        $this->readTableStyle($table, $this->tableXml->tableStyleInfo, $tableStyles, $dxfs);

        (new AutoFilter($table, $this->tableXml))->load();
        $this->worksheet->addTable($table);
    }

    
    private function readTableAutoFilter(Table $table, SimpleXMLElement $autoFilterXml): void
    {
        if ($autoFilterXml->filterColumn === null) {
            $table->setAllowFilter(false);

            return;
        }

        foreach ($autoFilterXml->filterColumn as $filterColumn) {
            $attributes = $filterColumn->attributes() ?? ['colId' => 0, 'hiddenButton' => 0];
            $column = $table->getColumnByOffset((int) $attributes['colId']);
            $column->setShowFilterButton(((string) $attributes['hiddenButton']) !== '1');
        }
    }

    
    private function readTableColumns(Table $table, SimpleXMLElement $tableColumnsXml): void
    {
        $offset = 0;
        foreach ($tableColumnsXml->tableColumn as $tableColumn) {
            $attributes = $tableColumn->attributes() ?? ['totalsRowLabel' => 0, 'totalsRowFunction' => 0];
            $column = $table->getColumnByOffset($offset++);

            if ($table->getShowTotalsRow()) {
                if ($attributes['totalsRowLabel']) {
                    $column->setTotalsRowLabel((string) $attributes['totalsRowLabel']);
                }

                if ($attributes['totalsRowFunction']) {
                    $column->setTotalsRowFunction((string) $attributes['totalsRowFunction']);
                }
            }

            if ($tableColumn->calculatedColumnFormula) {
                $column->setColumnFormula((string) $tableColumn->calculatedColumnFormula);
            }
        }
    }

    
    private function readTableStyle(Table $table, SimpleXMLElement $tableStyleInfoXml, array $tableStyles, array $dxfs): void
    {
        $tableStyle = new TableStyle();
        $attributes = $tableStyleInfoXml->attributes();
        if ($attributes !== null) {
            $tableStyle->setTheme((string) $attributes['name']);
            $tableStyle->setShowRowStripes((string) $attributes['showRowStripes'] === '1');
            $tableStyle->setShowColumnStripes((string) $attributes['showColumnStripes'] === '1');
            $tableStyle->setShowFirstColumn((string) $attributes['showFirstColumn'] === '1');
            $tableStyle->setShowLastColumn((string) $attributes['showLastColumn'] === '1');

            foreach ($tableStyles as $style) {
                if ($style->getName() === (string) $attributes['name']) {
                    $tableStyle->setTableDxfsStyle($style, $dxfs);
                }
            }
        }
        $table->setStyle($tableStyle);
    }
}
