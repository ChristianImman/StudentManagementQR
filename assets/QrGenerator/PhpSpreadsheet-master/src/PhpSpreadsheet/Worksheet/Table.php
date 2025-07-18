<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use Stringable;

class Table implements Stringable
{
    
    private string $name;

    
    private bool $showHeaderRow = true;

    
    private bool $showTotalsRow = false;

    
    private string $range = '';

    
    private ?Worksheet $workSheet = null;

    
    private bool $allowFilter = true;

    
    private array $columns = [];

    
    private TableStyle $style;

    
    private AutoFilter $autoFilter;

    
    public function __construct(AddressRange|string|array $range = '', string $name = '')
    {
        $this->style = new TableStyle();
        $this->autoFilter = new AutoFilter($range);
        $this->setRange($range);
        $this->setName($name);
    }

    
    public function __destruct()
    {
        $this->workSheet = null;
    }

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function setName(string $name): self
    {
        $name = trim($name);

        if (!empty($name)) {
            if (strlen($name) === 1 && in_array($name, ['C', 'c', 'R', 'r'])) {
                throw new PhpSpreadsheetException('The table name is invalid');
            }
            if (StringHelper::countCharacters($name) > 255) {
                throw new PhpSpreadsheetException('The table name cannot be longer than 255 characters');
            }
            
            if (
                preg_match(Coordinate::A1_COORDINATE_REGEX, $name)
                || preg_match('/^R\[?\-?[0-9]*\]?C\[?\-?[0-9]*\]?$/i', $name)
            ) {
                throw new PhpSpreadsheetException('The table name can\'t be the same as a cell reference');
            }
            if (!preg_match('/^[\p{L}_\\\]/iu', $name)) {
                throw new PhpSpreadsheetException('The table name must begin a name with a letter, an underscore character (_), or a backslash (\)');
            }
            if (!preg_match('/^[\p{L}_\\\][\p{L}\p{M}0-9\._]+$/iu', $name)) {
                throw new PhpSpreadsheetException('The table name contains invalid characters');
            }

            $this->checkForDuplicateTableNames($name, $this->workSheet);
            $this->updateStructuredReferences($name);
        }

        $this->name = $name;

        return $this;
    }

    
    private function checkForDuplicateTableNames(string $name, ?Worksheet $worksheet): void
    {
        
        $tableName = StringHelper::strToLower($name);

        if ($worksheet !== null && StringHelper::strToLower($this->name) !== $name) {
            $spreadsheet = $worksheet->getParentOrThrow();

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                foreach ($sheet->getTableCollection() as $table) {
                    if (StringHelper::strToLower($table->getName()) === $tableName && $table != $this) {
                        throw new PhpSpreadsheetException("Spreadsheet already contains a table named '{$this->name}'");
                    }
                }
            }
        }
    }

    private function updateStructuredReferences(string $name): void
    {
        if (!$this->workSheet || !$this->name) {
            return;
        }

        
        if (StringHelper::strToLower($this->name) !== StringHelper::strToLower($name)) {
            
            
            $spreadsheet = $this->workSheet->getParentOrThrow();
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $this->updateStructuredReferencesInCells($sheet, $name);
            }
            $this->updateStructuredReferencesInNamedFormulae($spreadsheet, $name);
        }
    }

    private function updateStructuredReferencesInCells(Worksheet $worksheet, string $newName): void
    {
        $pattern = '/' . preg_quote($this->name, '/') . '\[/mui';

        foreach ($worksheet->getCoordinates(false) as $coordinate) {
            $cell = $worksheet->getCell($coordinate);
            if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                $formula = $cell->getValueString();
                if (preg_match($pattern, $formula) === 1) {
                    $formula = preg_replace($pattern, "{$newName}[", $formula);
                    $cell->setValueExplicit($formula, DataType::TYPE_FORMULA);
                }
            }
        }
    }

    private function updateStructuredReferencesInNamedFormulae(Spreadsheet $spreadsheet, string $newName): void
    {
        $pattern = '/' . preg_quote($this->name, '/') . '\[/mui';

        foreach ($spreadsheet->getNamedFormulae() as $namedFormula) {
            $formula = $namedFormula->getValue();
            if (preg_match($pattern, $formula) === 1) {
                $formula = preg_replace($pattern, "{$newName}[", $formula) ?? '';
                $namedFormula->setValue($formula);
            }
        }
    }

    
    public function getShowHeaderRow(): bool
    {
        return $this->showHeaderRow;
    }

    
    public function setShowHeaderRow(bool $showHeaderRow): self
    {
        $this->showHeaderRow = $showHeaderRow;

        return $this;
    }

    
    public function getShowTotalsRow(): bool
    {
        return $this->showTotalsRow;
    }

    
    public function setShowTotalsRow(bool $showTotalsRow): self
    {
        $this->showTotalsRow = $showTotalsRow;

        return $this;
    }

    
    public function getAllowFilter(): bool
    {
        return $this->allowFilter;
    }

    
    public function setAllowFilter(bool $allowFilter): self
    {
        $this->allowFilter = $allowFilter;

        return $this;
    }

    
    public function getRange(): string
    {
        return $this->range;
    }

    
    public function setRange(AddressRange|string|array $range = ''): self
    {
        
        if ($range !== '') {
            [, $range] = Worksheet::extractSheetTitle(Validations::validateCellRange($range), true);
        }
        if (empty($range)) {
            
            $this->columns = [];
            $this->range = '';

            return $this;
        }

        if (!str_contains($range, ':')) {
            throw new PhpSpreadsheetException('Table must be set on a range of cells.');
        }

        [$width, $height] = Coordinate::rangeDimension($range);
        if ($width < 1 || $height < 1) {
            throw new PhpSpreadsheetException('The table range must be at least 1 column and row');
        }

        $this->range = $range;
        $this->autoFilter->setRange($range);

        
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($this->range);
        foreach ($this->columns as $key => $value) {
            $colIndex = Coordinate::columnIndexFromString($key);
            if (($rangeStart[0] > $colIndex) || ($rangeEnd[0] < $colIndex)) {
                unset($this->columns[$key]);
            }
        }

        return $this;
    }

    
    public function setRangeToMaxRow(): self
    {
        if ($this->workSheet !== null) {
            $thisrange = $this->range;
            $range = (string) preg_replace('/\d+$/', (string) $this->workSheet->getHighestRow(), $thisrange);
            if ($range !== $thisrange) {
                $this->setRange($range);
            }
        }

        return $this;
    }

    
    public function getWorksheet(): ?Worksheet
    {
        return $this->workSheet;
    }

    
    public function setWorksheet(?Worksheet $worksheet = null): self
    {
        if ($this->name !== '' && $worksheet !== null) {
            $spreadsheet = $worksheet->getParentOrThrow();
            $tableName = StringHelper::strToUpper($this->name);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                foreach ($sheet->getTableCollection() as $table) {
                    if (StringHelper::strToUpper($table->getName()) === $tableName) {
                        throw new PhpSpreadsheetException("Workbook already contains a table named '{$this->name}'");
                    }
                }
            }
        }

        $this->workSheet = $worksheet;
        $this->autoFilter->setParent($worksheet);

        return $this;
    }

    
    public function getColumns(): array
    {
        return $this->columns;
    }

    
    public function isColumnInRange(string $column): int
    {
        if (empty($this->range)) {
            throw new PhpSpreadsheetException('No table range is defined.');
        }

        $columnIndex = Coordinate::columnIndexFromString($column);
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($this->range);
        if (($rangeStart[0] > $columnIndex) || ($rangeEnd[0] < $columnIndex)) {
            throw new PhpSpreadsheetException('Column is outside of current table range.');
        }

        return $columnIndex - $rangeStart[0];
    }

    
    public function getColumnOffset(string $column): int
    {
        return $this->isColumnInRange($column);
    }

    
    public function getColumn(string $column): Table\Column
    {
        $this->isColumnInRange($column);

        if (!isset($this->columns[$column])) {
            $this->columns[$column] = new Table\Column($column, $this);
        }

        return $this->columns[$column];
    }

    
    public function getColumnByOffset(int $columnOffset): Table\Column
    {
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($this->range);
        $pColumn = Coordinate::stringFromColumnIndex($rangeStart[0] + $columnOffset);

        return $this->getColumn($pColumn);
    }

    
    public function setColumn(string|Table\Column $columnObjectOrString): self
    {
        if ((is_string($columnObjectOrString)) && (!empty($columnObjectOrString))) {
            $column = $columnObjectOrString;
        } elseif ($columnObjectOrString instanceof Table\Column) {
            $column = $columnObjectOrString->getColumnIndex();
        } else {
            throw new PhpSpreadsheetException('Column is not within the table range.');
        }
        $this->isColumnInRange($column);

        if (is_string($columnObjectOrString)) {
            $this->columns[$columnObjectOrString] = new Table\Column($columnObjectOrString, $this);
        } else {
            $columnObjectOrString->setTable($this);
            $this->columns[$column] = $columnObjectOrString;
        }
        ksort($this->columns);

        return $this;
    }

    
    public function clearColumn(string $column): self
    {
        $this->isColumnInRange($column);

        if (isset($this->columns[$column])) {
            unset($this->columns[$column]);
        }

        return $this;
    }

    
    public function shiftColumn(string $fromColumn, string $toColumn): self
    {
        $fromColumn = strtoupper($fromColumn);
        $toColumn = strtoupper($toColumn);

        if (isset($this->columns[$fromColumn])) {
            $this->columns[$fromColumn]->setTable();
            $this->columns[$fromColumn]->setColumnIndex($toColumn);
            $this->columns[$toColumn] = $this->columns[$fromColumn];
            $this->columns[$toColumn]->setTable($this);
            unset($this->columns[$fromColumn]);

            ksort($this->columns);
        }

        return $this;
    }

    
    public function getStyle(): TableStyle
    {
        return $this->style;
    }

    
    public function setStyle(TableStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    
    public function getAutoFilter(): AutoFilter
    {
        return $this->autoFilter;
    }

    
    public function setAutoFilter(AutoFilter $autoFilter): self
    {
        $this->autoFilter = $autoFilter;

        return $this;
    }

    
    public function getRowNumber(string $coordinate): int
    {
        $range = $this->getRange();
        $coords = Coordinate::splitRange($range);
        $firstCell = Coordinate::coordinateFromString($coords[0][0]);
        $thisCell = Coordinate::coordinateFromString($coordinate);

        return (int) $thisCell[1] - (int) $firstCell[1];
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                if ($key === 'workSheet') {
                    
                    $this->{$key} = null;
                } else {
                    $this->{$key} = clone $value;
                }
            } elseif ((is_array($value)) && ($key === 'columns')) {
                
                $this->{$key} = [];
                foreach ($value as $k => $v) {
                    
                    $this->{$key}[$k] = clone $v;
                    
                    $this->{$key}[$k]->setTable($this);
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }

    
    public function __toString(): string
    {
        return (string) $this->range;
    }
}
