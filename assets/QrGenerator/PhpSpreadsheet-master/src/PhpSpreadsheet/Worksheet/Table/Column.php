<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\Table;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Column
{
    
    private string $columnIndex;

    
    private bool $showFilterButton = true;

    
    private ?string $totalsRowLabel = null;

    
    private ?string $totalsRowFunction = null;

    
    private ?string $totalsRowFormula = null;

    
    private ?string $columnFormula = null;

    
    private ?Table $table;

    
    public function __construct(string $column, ?Table $table = null)
    {
        $this->columnIndex = $column;
        $this->table = $table;
    }

    
    public function getColumnIndex(): string
    {
        return $this->columnIndex;
    }

    
    public function setColumnIndex(string $column): self
    {
        
        $column = strtoupper($column);
        if ($this->table !== null) {
            $this->table->isColumnInRange($column);
        }

        $this->columnIndex = $column;

        return $this;
    }

    
    public function getShowFilterButton(): bool
    {
        return $this->showFilterButton;
    }

    
    public function setShowFilterButton(bool $showFilterButton): self
    {
        $this->showFilterButton = $showFilterButton;

        return $this;
    }

    
    public function getTotalsRowLabel(): ?string
    {
        return $this->totalsRowLabel;
    }

    
    public function setTotalsRowLabel(string $totalsRowLabel): self
    {
        $this->totalsRowLabel = $totalsRowLabel;

        return $this;
    }

    
    public function getTotalsRowFunction(): ?string
    {
        return $this->totalsRowFunction;
    }

    
    public function setTotalsRowFunction(string $totalsRowFunction): self
    {
        $this->totalsRowFunction = $totalsRowFunction;

        return $this;
    }

    
    public function getTotalsRowFormula(): ?string
    {
        return $this->totalsRowFormula;
    }

    
    public function setTotalsRowFormula(string $totalsRowFormula): self
    {
        $this->totalsRowFormula = $totalsRowFormula;

        return $this;
    }

    
    public function getColumnFormula(): ?string
    {
        return $this->columnFormula;
    }

    
    public function setColumnFormula(string $columnFormula): self
    {
        $this->columnFormula = $columnFormula;

        return $this;
    }

    
    public function getTable(): ?Table
    {
        return $this->table;
    }

    
    public function setTable(?Table $table = null): self
    {
        $this->table = $table;

        return $this;
    }

    public static function updateStructuredReferences(?Worksheet $workSheet, ?string $oldTitle, ?string $newTitle): void
    {
        if ($workSheet === null || $oldTitle === null || $oldTitle === '' || $newTitle === null) {
            return;
        }

        
        if (StringHelper::strToLower($oldTitle) !== StringHelper::strToLower($newTitle)) {
            
            
            $spreadsheet = $workSheet->getParentOrThrow();
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                self::updateStructuredReferencesInCells($sheet, $oldTitle, $newTitle);
            }
            self::updateStructuredReferencesInNamedFormulae($spreadsheet, $oldTitle, $newTitle);
        }
    }

    private static function updateStructuredReferencesInCells(Worksheet $worksheet, string $oldTitle, string $newTitle): void
    {
        $pattern = '/\[(@?)' . preg_quote($oldTitle, '/') . '\]/mui';

        foreach ($worksheet->getCoordinates(false) as $coordinate) {
            $cell = $worksheet->getCell($coordinate);
            if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                $formula = $cell->getValueString();
                if (preg_match($pattern, $formula) === 1) {
                    $formula = preg_replace($pattern, "[$1{$newTitle}]", $formula);
                    $cell->setValueExplicit($formula, DataType::TYPE_FORMULA);
                }
            }
        }
    }

    private static function updateStructuredReferencesInNamedFormulae(Spreadsheet $spreadsheet, string $oldTitle, string $newTitle): void
    {
        $pattern = '/\[(@?)' . preg_quote($oldTitle, '/') . '\]/mui';

        foreach ($spreadsheet->getNamedFormulae() as $namedFormula) {
            $formula = $namedFormula->getValue();
            if (preg_match($pattern, $formula) === 1) {
                $formula = preg_replace($pattern, "[$1{$newTitle}]", $formula) ?? '';
                $namedFormula->setValue($formula);
            }
        }
    }
}
