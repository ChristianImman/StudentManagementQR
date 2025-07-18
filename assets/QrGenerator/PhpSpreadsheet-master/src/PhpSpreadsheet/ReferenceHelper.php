<?php

namespace PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferenceHelper
{
    
    
    private const SHEETNAME_PART = '((\w*|\'[^!]*\')!)';
    private const SHEETNAME_PART_WITH_SLASHES = '/' . self::SHEETNAME_PART . '/';
    const REFHELPER_REGEXP_CELLREF = self::SHEETNAME_PART . '?(?<![:a-z1-9_\.\$])(\$?[a-z]{1,3}\$?\d+)(?=[^:!\d\'])';
    const REFHELPER_REGEXP_CELLRANGE = self::SHEETNAME_PART . '?(\$?[a-z]{1,3}\$?\d+):(\$?[a-z]{1,3}\$?\d+)';
    const REFHELPER_REGEXP_ROWRANGE = self::SHEETNAME_PART . '?(\$?\d+):(\$?\d+)';
    const REFHELPER_REGEXP_COLRANGE = self::SHEETNAME_PART . '?(\$?[a-z]{1,3}):(\$?[a-z]{1,3})';

    
    private static ?ReferenceHelper $instance = null;

    private ?CellReferenceHelper $cellReferenceHelper = null;

    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    
    protected function __construct()
    {
    }

    
    public static function columnSort(string $a, string $b): int
    {
        return strcasecmp(strlen($a) . $a, strlen($b) . $b);
    }

    
    public static function columnReverseSort(string $a, string $b): int
    {
        return -strcasecmp(strlen($a) . $a, strlen($b) . $b);
    }

    
    public static function cellSort(string $a, string $b): int
    {
        sscanf($a, '%[A-Z]%d', $ac, $ar);
        
        
        sscanf($b, '%[A-Z]%d', $bc, $br);
        
        
        if ($ar === $br) {
            return strcasecmp(strlen($ac) . $ac, strlen($bc) . $bc);
        }

        return ($ar < $br) ? -1 : 1;
    }

    
    public static function cellReverseSort(string $a, string $b): int
    {
        sscanf($a, '%[A-Z]%d', $ac, $ar);
        
        
        sscanf($b, '%[A-Z]%d', $bc, $br);
        
        
        if ($ar === $br) {
            return -strcasecmp(strlen($ac) . $ac, strlen($bc) . $bc);
        }

        return ($ar < $br) ? 1 : -1;
    }

    
    protected function adjustPageBreaks(Worksheet $worksheet, int $numberOfColumns, int $numberOfRows): void
    {
        $aBreaks = $worksheet->getBreaks();
        ($numberOfColumns > 0 || $numberOfRows > 0)
            ? uksort($aBreaks, [self::class, 'cellReverseSort'])
            : uksort($aBreaks, [self::class, 'cellSort']);

        foreach ($aBreaks as $cellAddress => $value) {
            
            $cellReferenceHelper = $this->cellReferenceHelper;
            if ($cellReferenceHelper->cellAddressInDeleteRange($cellAddress) === true) {
                
                
                $worksheet->setBreak($cellAddress, Worksheet::BREAK_NONE);
            } else {
                
                
                $newReference = $this->updateCellReference($cellAddress);
                if ($cellAddress !== $newReference) {
                    $worksheet->setBreak($newReference, $value)
                        ->setBreak($cellAddress, Worksheet::BREAK_NONE);
                }
            }
        }
    }

    
    protected function adjustComments(Worksheet $worksheet): void
    {
        $aComments = $worksheet->getComments();
        $aNewComments = []; 

        foreach ($aComments as $cellAddress => &$value) {
            
            
            $cellReferenceHelper = $this->cellReferenceHelper;
            if ($cellReferenceHelper->cellAddressInDeleteRange($cellAddress) === false) {
                
                $newReference = $this->updateCellReference($cellAddress);
                $aNewComments[$newReference] = $value;
            }
        }
        
        $worksheet->setComments($aNewComments);
    }

    
    protected function adjustHyperlinks(Worksheet $worksheet, int $numberOfColumns, int $numberOfRows): void
    {
        $aHyperlinkCollection = $worksheet->getHyperlinkCollection();
        ($numberOfColumns > 0 || $numberOfRows > 0)
            ? uksort($aHyperlinkCollection, [self::class, 'cellReverseSort'])
            : uksort($aHyperlinkCollection, [self::class, 'cellSort']);

        foreach ($aHyperlinkCollection as $cellAddress => $value) {
            $newReference = $this->updateCellReference($cellAddress);
            
            $cellReferenceHelper = $this->cellReferenceHelper;
            if ($cellReferenceHelper->cellAddressInDeleteRange($cellAddress) === true) {
                $worksheet->setHyperlink($cellAddress, null);
            } elseif ($cellAddress !== $newReference) {
                $worksheet->setHyperlink($cellAddress, null);
                if ($newReference) {
                    $worksheet->setHyperlink($newReference, $value);
                }
            }
        }
    }

    
    protected function adjustConditionalFormatting(Worksheet $worksheet, int $numberOfColumns, int $numberOfRows): void
    {
        $aStyles = $worksheet->getConditionalStylesCollection();
        ($numberOfColumns > 0 || $numberOfRows > 0)
            ? uksort($aStyles, [self::class, 'cellReverseSort'])
            : uksort($aStyles, [self::class, 'cellSort']);

        foreach ($aStyles as $cellAddress => $cfRules) {
            $worksheet->removeConditionalStyles($cellAddress);
            $newReference = $this->updateCellReference($cellAddress);

            foreach ($cfRules as &$cfRule) {
                
                $conditions = $cfRule->getConditions();
                foreach ($conditions as &$condition) {
                    if (is_string($condition)) {
                        
                        $cellReferenceHelper = $this->cellReferenceHelper;
                        $condition = $this->updateFormulaReferences(
                            $condition,
                            $cellReferenceHelper->beforeCellAddress(),
                            $numberOfColumns,
                            $numberOfRows,
                            $worksheet->getTitle(),
                            true
                        );
                    }
                }
                $cfRule->setConditions($conditions);
            }
            $worksheet->setConditionalStyles($newReference, $cfRules);
        }
    }

    
    protected function adjustDataValidations(Worksheet $worksheet, int $numberOfColumns, int $numberOfRows, string $beforeCellAddress): void
    {
        $aDataValidationCollection = $worksheet->getDataValidationCollection();
        ($numberOfColumns > 0 || $numberOfRows > 0)
            ? uksort($aDataValidationCollection, [self::class, 'cellReverseSort'])
            : uksort($aDataValidationCollection, [self::class, 'cellSort']);

        foreach ($aDataValidationCollection as $cellAddress => $dataValidation) {
            $formula = $dataValidation->getFormula1();
            if ($formula !== '') {
                $dataValidation->setFormula1(
                    $this->updateFormulaReferences(
                        $formula,
                        $beforeCellAddress,
                        $numberOfColumns,
                        $numberOfRows,
                        $worksheet->getTitle(),
                        true
                    )
                );
            }
            $formula = $dataValidation->getFormula2();
            if ($formula !== '') {
                $dataValidation->setFormula2(
                    $this->updateFormulaReferences(
                        $formula,
                        $beforeCellAddress,
                        $numberOfColumns,
                        $numberOfRows,
                        $worksheet->getTitle(),
                        true
                    )
                );
            }
            $addressParts = explode(' ', $cellAddress);
            $newReference = '';
            $separator = '';
            foreach ($addressParts as $addressPart) {
                $newReference .= $separator . $this->updateCellReference($addressPart);
                $separator = ' ';
            }
            if ($cellAddress !== $newReference) {
                $worksheet->setDataValidation($newReference, $dataValidation);
                $worksheet->setDataValidation($cellAddress, null);
                if ($newReference) {
                    $worksheet->setDataValidation($newReference, $dataValidation);
                }
            }
        }
    }

    
    protected function adjustMergeCells(Worksheet $worksheet): void
    {
        $aMergeCells = $worksheet->getMergeCells();
        $aNewMergeCells = []; 
        foreach ($aMergeCells as $cellAddress => &$value) {
            $newReference = $this->updateCellReference($cellAddress);
            if ($newReference) {
                $aNewMergeCells[$newReference] = $newReference;
            }
        }
        $worksheet->setMergeCells($aNewMergeCells); 
    }

    
    protected function adjustProtectedCells(Worksheet $worksheet, int $numberOfColumns, int $numberOfRows): void
    {
        $aProtectedCells = $worksheet->getProtectedCellRanges();
        ($numberOfColumns > 0 || $numberOfRows > 0)
            ? uksort($aProtectedCells, [self::class, 'cellReverseSort'])
            : uksort($aProtectedCells, [self::class, 'cellSort']);
        foreach ($aProtectedCells as $cellAddress => $protectedRange) {
            $newReference = $this->updateCellReference($cellAddress);
            if ($cellAddress !== $newReference) {
                $worksheet->unprotectCells($cellAddress);
                if ($newReference) {
                    $worksheet->protectCells($newReference, $protectedRange->getPassword(), true);
                }
            }
        }
    }

    
    protected function adjustColumnDimensions(Worksheet $worksheet): void
    {
        $aColumnDimensions = array_reverse($worksheet->getColumnDimensions(), true);
        if (!empty($aColumnDimensions)) {
            foreach ($aColumnDimensions as $objColumnDimension) {
                $newReference = $this->updateCellReference($objColumnDimension->getColumnIndex() . '1');
                [$newReference] = Coordinate::coordinateFromString($newReference);
                if ($objColumnDimension->getColumnIndex() !== $newReference) {
                    $objColumnDimension->setColumnIndex($newReference);
                }
            }

            $worksheet->refreshColumnDimensions();
        }
    }

    
    protected function adjustRowDimensions(Worksheet $worksheet, int $beforeRow, int $numberOfRows): void
    {
        $aRowDimensions = array_reverse($worksheet->getRowDimensions(), true);
        if (!empty($aRowDimensions)) {
            foreach ($aRowDimensions as $objRowDimension) {
                $newReference = $this->updateCellReference('A' . $objRowDimension->getRowIndex());
                [, $newReference] = Coordinate::coordinateFromString($newReference);
                $newRoweference = (int) $newReference;
                if ($objRowDimension->getRowIndex() !== $newRoweference) {
                    $objRowDimension->setRowIndex($newRoweference);
                }
            }

            $worksheet->refreshRowDimensions();

            $copyDimension = $worksheet->getRowDimension($beforeRow - 1);
            for ($i = $beforeRow; $i <= $beforeRow - 1 + $numberOfRows; ++$i) {
                $newDimension = $worksheet->getRowDimension($i);
                $newDimension->setRowHeight($copyDimension->getRowHeight());
                $newDimension->setVisible($copyDimension->getVisible());
                $newDimension->setOutlineLevel($copyDimension->getOutlineLevel());
                $newDimension->setCollapsed($copyDimension->getCollapsed());
            }
        }
    }

    
    public function insertNewBefore(
        string $beforeCellAddress,
        int $numberOfColumns,
        int $numberOfRows,
        Worksheet $worksheet
    ): void {
        $remove = ($numberOfColumns < 0 || $numberOfRows < 0);

        if (
            $this->cellReferenceHelper === null
            || $this->cellReferenceHelper->refreshRequired($beforeCellAddress, $numberOfColumns, $numberOfRows)
        ) {
            $this->cellReferenceHelper = new CellReferenceHelper($beforeCellAddress, $numberOfColumns, $numberOfRows);
        }

        
        [$beforeColumn, $beforeRow, $beforeColumnString] = Coordinate::indexesFromString($beforeCellAddress);

        
        $highestColumn = $worksheet->getHighestColumn();
        $highestDataColumn = $worksheet->getHighestDataColumn();
        $highestRow = $worksheet->getHighestRow();
        $highestDataRow = $worksheet->getHighestDataRow();

        
        if ($numberOfColumns < 0 && $beforeColumn - 2 + $numberOfColumns > 0) {
            $this->clearColumnStrips($highestRow, $beforeColumn, $numberOfColumns, $worksheet);
        }

        
        if ($numberOfRows < 0 && $beforeRow - 1 + $numberOfRows > 0) {
            $this->clearRowStrips($highestColumn, $beforeColumn, $beforeRow, $numberOfRows, $worksheet);
        }

        
        $startRow = $startCol = 1;
        $startColString = 'A';
        if ($numberOfRows === 0) {
            $startCol = $beforeColumn;
            $startColString = $beforeColumnString;
        } elseif ($numberOfColumns === 0) {
            $startRow = $beforeRow;
        }
        $highColumn = Coordinate::columnIndexFromString($highestDataColumn);
        for ($row = $startRow; $row <= $highestDataRow; ++$row) {
            for ($col = $startCol, $colString = $startColString; $col <= $highColumn; ++$col, ++$colString) {
                $worksheet->getCell("$colString$row"); 
            }
        }

        $allCoordinates = $worksheet->getCoordinates();
        if ($remove) {
            
            $allCoordinates = array_reverse($allCoordinates);
        }

        
        while ($coordinate = array_pop($allCoordinates)) {
            $cell = $worksheet->getCell($coordinate);
            $cellIndex = Coordinate::columnIndexFromString($cell->getColumn());

            
            if ($numberOfColumns < 0 && $cellIndex >= $beforeColumn + $numberOfColumns && $cellIndex < $beforeColumn) {
                continue;
            }

            
            $newCoordinate = Coordinate::stringFromColumnIndex($cellIndex + $numberOfColumns) . ($cell->getRow() + $numberOfRows);

            
            if (($cellIndex >= $beforeColumn) && ($cell->getRow() >= $beforeRow)) {
                
                $worksheet->getCell($newCoordinate)->setXfIndex($cell->getXfIndex());

                
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    
                    $worksheet->getCell($newCoordinate)
                        ->setValue($this->updateFormulaReferences($cell->getValueString(), $beforeCellAddress, $numberOfColumns, $numberOfRows, $worksheet->getTitle(), true));
                } else {
                    
                    $worksheet->getCell($newCoordinate)->setValueExplicit($cell->getValue(), $cell->getDataType());
                }

                
                $worksheet->getCellCollection()->delete($coordinate);
            } else {
                
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    
                    $cell->setValue($this->updateFormulaReferences($cell->getValueString(), $beforeCellAddress, $numberOfColumns, $numberOfRows, $worksheet->getTitle(), true));
                }
            }
        }

        
        $highestColumn = $worksheet->getHighestColumn();
        $highestRow = $worksheet->getHighestRow();

        if ($numberOfColumns > 0 && $beforeColumn - 2 > 0) {
            $this->duplicateStylesByColumn($worksheet, $beforeColumn, $beforeRow, $highestRow, $numberOfColumns);
        }

        if ($numberOfRows > 0 && $beforeRow - 1 > 0) {
            $this->duplicateStylesByRow($worksheet, $beforeColumn, $beforeRow, $highestColumn, $numberOfRows);
        }

        
        $this->adjustColumnDimensions($worksheet);

        
        $this->adjustRowDimensions($worksheet, $beforeRow, $numberOfRows);

        
        $this->adjustPageBreaks($worksheet, $numberOfColumns, $numberOfRows);

        
        $this->adjustComments($worksheet);

        
        $this->adjustHyperlinks($worksheet, $numberOfColumns, $numberOfRows);

        
        $this->adjustConditionalFormatting($worksheet, $numberOfColumns, $numberOfRows);

        
        $this->adjustDataValidations($worksheet, $numberOfColumns, $numberOfRows, $beforeCellAddress);

        
        $this->adjustMergeCells($worksheet);

        
        $this->adjustProtectedCells($worksheet, $numberOfColumns, $numberOfRows);

        
        $this->adjustAutoFilter($worksheet, $beforeCellAddress, $numberOfColumns);

        
        $this->adjustTable($worksheet, $beforeCellAddress, $numberOfColumns);

        
        if ($worksheet->getFreezePane()) {
            $splitCell = $worksheet->getFreezePane();
            $topLeftCell = $worksheet->getTopLeftCell() ?? '';

            $splitCell = $this->updateCellReference($splitCell);
            $topLeftCell = $this->updateCellReference($topLeftCell);

            $worksheet->freezePane($splitCell, $topLeftCell);
        }

        
        if ($worksheet->getPageSetup()->isPrintAreaSet()) {
            $worksheet->getPageSetup()->setPrintArea(
                $this->updateCellReference($worksheet->getPageSetup()->getPrintArea())
            );
        }

        
        $aDrawings = $worksheet->getDrawingCollection();
        foreach ($aDrawings as $objDrawing) {
            $newReference = $this->updateCellReference($objDrawing->getCoordinates());
            if ($objDrawing->getCoordinates() != $newReference) {
                $objDrawing->setCoordinates($newReference);
            }
            if ($objDrawing->getCoordinates2() !== '') {
                $newReference = $this->updateCellReference($objDrawing->getCoordinates2());
                if ($objDrawing->getCoordinates2() != $newReference) {
                    $objDrawing->setCoordinates2($newReference);
                }
            }
        }

        
        if (count($worksheet->getParentOrThrow()->getDefinedNames()) > 0) {
            $this->updateDefinedNames($worksheet, $beforeCellAddress, $numberOfColumns, $numberOfRows);
        }

        
        $worksheet->garbageCollect();
    }

    private static function matchSheetName(?string $match, string $worksheetName): bool
    {
        return $match === null || $match === '' || $match === "'\u{fffc}'" || $match === "'\u{fffb}'" || strcasecmp(trim($match, "'"), $worksheetName) === 0;
    }

    private static function sheetnameBeforeCells(string $match, string $worksheetName, string $cells): string
    {
        $toString = ($match > '') ? "$match!" : '';

        return str_replace(["\u{fffc}", "'\u{fffb}'"], $worksheetName, $toString) . $cells;
    }

    
    public function updateFormulaReferences(
        string $formula = '',
        string $beforeCellAddress = 'A1',
        int $numberOfColumns = 0,
        int $numberOfRows = 0,
        string $worksheetName = '',
        bool $includeAbsoluteReferences = false,
        bool $onlyAbsoluteReferences = false
    ): string {
        $callback = fn (array $matches): string => (strcasecmp(trim($matches[2], "'"), $worksheetName) === 0) ? (($matches[2][0] === "'") ? "'\u{fffc}'!" : "'\u{fffb}'!") : "'\u{fffd}'!";
        if (
            $this->cellReferenceHelper === null
            || $this->cellReferenceHelper->refreshRequired($beforeCellAddress, $numberOfColumns, $numberOfRows)
        ) {
            $this->cellReferenceHelper = new CellReferenceHelper($beforeCellAddress, $numberOfColumns, $numberOfRows);
        }

        
        $formulaBlocks = explode('"', $formula);
        $i = false;
        foreach ($formulaBlocks as &$formulaBlock) {
            
            $i = $i === false;
            if ($i) {
                $adjustCount = 0;
                $newCellTokens = $cellTokens = [];
                
                $formulaBlockx = ' ' . (preg_replace_callback(self::SHEETNAME_PART_WITH_SLASHES, $callback, $formulaBlock) ?? $formulaBlock) . ' ';
                $matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_ROWRANGE . '/mui', $formulaBlockx, $matches, PREG_SET_ORDER);
                if ($matchCount > 0) {
                    foreach ($matches as $match) {
                        $fromString = self::sheetnameBeforeCells($match[2], $worksheetName, "{$match[3]}:{$match[4]}");
                        $modified3 = substr($this->updateCellReference('$A' . $match[3], $includeAbsoluteReferences, $onlyAbsoluteReferences, true), 2);
                        $modified4 = substr($this->updateCellReference('$A' . $match[4], $includeAbsoluteReferences, $onlyAbsoluteReferences, false), 2);

                        if ($match[3] . ':' . $match[4] !== $modified3 . ':' . $modified4) {
                            if (self::matchSheetName($match[2], $worksheetName)) {
                                $toString = self::sheetnameBeforeCells($match[2], $worksheetName, "$modified3:$modified4");
                                
                                $column = 100000;
                                $row = 10000000 + (int) trim($match[3], '$');
                                $cellIndex = "{$column}{$row}";

                                $newCellTokens[$cellIndex] = preg_quote($toString, '/');
                                $cellTokens[$cellIndex] = '/(?<!\d\$\!)' . preg_quote($fromString, '/') . '(?!\d)/i';
                                ++$adjustCount;
                            }
                        }
                    }
                }
                
                $formulaBlockx = ' ' . (preg_replace_callback(self::SHEETNAME_PART_WITH_SLASHES, $callback, $formulaBlock) ?? $formulaBlock) . ' ';
                $matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_COLRANGE . '/mui', $formulaBlockx, $matches, PREG_SET_ORDER);
                if ($matchCount > 0) {
                    foreach ($matches as $match) {
                        $fromString = self::sheetnameBeforeCells($match[2], $worksheetName, "{$match[3]}:{$match[4]}");
                        $modified3 = substr($this->updateCellReference($match[3] . '$1', $includeAbsoluteReferences, $onlyAbsoluteReferences, true), 0, -2);
                        $modified4 = substr($this->updateCellReference($match[4] . '$1', $includeAbsoluteReferences, $onlyAbsoluteReferences, false), 0, -2);

                        if ($match[3] . ':' . $match[4] !== $modified3 . ':' . $modified4) {
                            if (self::matchSheetName($match[2], $worksheetName)) {
                                $toString = self::sheetnameBeforeCells($match[2], $worksheetName, "$modified3:$modified4");
                                
                                $column = Coordinate::columnIndexFromString(trim($match[3], '$')) + 100000;
                                $row = 10000000;
                                $cellIndex = "{$column}{$row}";

                                $newCellTokens[$cellIndex] = preg_quote($toString, '/');
                                $cellTokens[$cellIndex] = '/(?<![A-Z\$\!])' . preg_quote($fromString, '/') . '(?![A-Z])/i';
                                ++$adjustCount;
                            }
                        }
                    }
                }
                
                $formulaBlockx = ' ' . (preg_replace_callback(self::SHEETNAME_PART_WITH_SLASHES, $callback, "$formulaBlock") ?? "$formulaBlock") . ' ';
                $matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_CELLRANGE . '/mui', $formulaBlockx, $matches, PREG_SET_ORDER);
                if ($matchCount > 0) {
                    foreach ($matches as $match) {
                        $fromString = self::sheetnameBeforeCells($match[2], $worksheetName, "{$match[3]}:{$match[4]}");
                        $modified3 = $this->updateCellReference($match[3], $includeAbsoluteReferences, $onlyAbsoluteReferences, true);
                        $modified4 = $this->updateCellReference($match[4], $includeAbsoluteReferences, $onlyAbsoluteReferences, false);

                        if ($match[3] . $match[4] !== $modified3 . $modified4) {
                            if (self::matchSheetName($match[2], $worksheetName)) {
                                $toString = self::sheetnameBeforeCells($match[2], $worksheetName, "$modified3:$modified4");
                                [$column, $row] = Coordinate::coordinateFromString($match[3]);
                                
                                $column = Coordinate::columnIndexFromString(trim($column, '$')) + 100000;
                                $row = (int) trim($row, '$') + 10000000;
                                $cellIndex = "{$column}{$row}";

                                $newCellTokens[$cellIndex] = preg_quote($toString, '/');
                                $cellTokens[$cellIndex] = '/(?<![A-Z]\$\!)' . preg_quote($fromString, '/') . '(?!\d)/i';
                                ++$adjustCount;
                            }
                        }
                    }
                }
                

                $formulaBlockx = ' ' . (preg_replace_callback(self::SHEETNAME_PART_WITH_SLASHES, $callback, $formulaBlock) ?? $formulaBlock) . ' ';
                $matchCount = preg_match_all('/' . self::REFHELPER_REGEXP_CELLREF . '/mui', $formulaBlockx, $matches, PREG_SET_ORDER);

                if ($matchCount > 0) {
                    foreach ($matches as $match) {
                        $fromString = self::sheetnameBeforeCells($match[2], $worksheetName, "{$match[3]}");

                        $modified3 = $this->updateCellReference($match[3], $includeAbsoluteReferences, $onlyAbsoluteReferences, null);
                        if ($match[3] !== $modified3) {
                            if (self::matchSheetName($match[2], $worksheetName)) {
                                $toString = self::sheetnameBeforeCells($match[2], $worksheetName, "$modified3");
                                [$column, $row] = Coordinate::coordinateFromString($match[3]);
                                $columnAdditionalIndex = $column[0] === '$' ? 1 : 0;
                                $rowAdditionalIndex = $row[0] === '$' ? 1 : 0;
                                
                                $column = Coordinate::columnIndexFromString(trim($column, '$')) + 100000;
                                $row = (int) trim($row, '$') + 10000000;
                                $cellIndex = $row . $rowAdditionalIndex . $column . $columnAdditionalIndex;

                                $newCellTokens[$cellIndex] = preg_quote($toString, '/');
                                $cellTokens[$cellIndex] = '/(?<![A-Z\$\!])' . preg_quote($fromString, '/') . '(?!\d)/i';
                                ++$adjustCount;
                            }
                        }
                    }
                }
                if ($adjustCount > 0) {
                    if ($numberOfColumns > 0 || $numberOfRows > 0) {
                        krsort($cellTokens);
                        krsort($newCellTokens);
                    } else {
                        ksort($cellTokens);
                        ksort($newCellTokens);
                    }   
                    $formulaBlock = str_replace('\\', '', (string) preg_replace($cellTokens, $newCellTokens, $formulaBlock));
                }
            }
        }
        unset($formulaBlock);

        
        return implode('"', $formulaBlocks);
    }

    
    public function updateFormulaReferencesAnyWorksheet(string $formula = '', int $numberOfColumns = 0, int $numberOfRows = 0): string
    {
        $formula = $this->updateCellReferencesAllWorksheets($formula, $numberOfColumns, $numberOfRows);

        if ($numberOfColumns !== 0) {
            $formula = $this->updateColumnRangesAllWorksheets($formula, $numberOfColumns);
        }

        if ($numberOfRows !== 0) {
            $formula = $this->updateRowRangesAllWorksheets($formula, $numberOfRows);
        }

        return $formula;
    }

    private function updateCellReferencesAllWorksheets(string $formula, int $numberOfColumns, int $numberOfRows): string
    {
        $splitCount = preg_match_all(
            '/' . Calculation::CALCULATION_REGEXP_CELLREF_RELATIVE . '/mui',
            $formula,
            $splitRanges,
            PREG_OFFSET_CAPTURE
        );

        $columnLengths = array_map('strlen', array_column($splitRanges[6], 0));
        $rowLengths = array_map('strlen', array_column($splitRanges[7], 0));
        $columnOffsets = array_column($splitRanges[6], 1);
        $rowOffsets = array_column($splitRanges[7], 1);

        $columns = $splitRanges[6];
        $rows = $splitRanges[7];

        while ($splitCount > 0) {
            --$splitCount;
            $columnLength = $columnLengths[$splitCount];
            $rowLength = $rowLengths[$splitCount];
            $columnOffset = $columnOffsets[$splitCount];
            $rowOffset = $rowOffsets[$splitCount];
            $column = $columns[$splitCount][0];
            $row = $rows[$splitCount][0];

            if ($column[0] !== '$') {
                $column = ((Coordinate::columnIndexFromString($column) + $numberOfColumns) % AddressRange::MAX_COLUMN_INT) ?: AddressRange::MAX_COLUMN_INT;
                $column = Coordinate::stringFromColumnIndex($column);
                $rowOffset -= ($columnLength - strlen($column));
                $formula = substr($formula, 0, $columnOffset) . $column . substr($formula, $columnOffset + $columnLength);
            }
            if (!empty($row) && $row[0] !== '$') {
                $row = (((int) $row + $numberOfRows) % AddressRange::MAX_ROW) ?: AddressRange::MAX_ROW;
                $formula = substr($formula, 0, $rowOffset) . $row . substr($formula, $rowOffset + $rowLength);
            }
        }

        return $formula;
    }

    private function updateColumnRangesAllWorksheets(string $formula, int $numberOfColumns): string
    {
        $splitCount = preg_match_all(
            '/' . Calculation::CALCULATION_REGEXP_COLUMNRANGE_RELATIVE . '/mui',
            $formula,
            $splitRanges,
            PREG_OFFSET_CAPTURE
        );

        $fromColumnLengths = array_map('strlen', array_column($splitRanges[1], 0));
        $fromColumnOffsets = array_column($splitRanges[1], 1);
        $toColumnLengths = array_map('strlen', array_column($splitRanges[2], 0));
        $toColumnOffsets = array_column($splitRanges[2], 1);

        $fromColumns = $splitRanges[1];
        $toColumns = $splitRanges[2];

        while ($splitCount > 0) {
            --$splitCount;
            $fromColumnLength = $fromColumnLengths[$splitCount];
            $toColumnLength = $toColumnLengths[$splitCount];
            $fromColumnOffset = $fromColumnOffsets[$splitCount];
            $toColumnOffset = $toColumnOffsets[$splitCount];
            $fromColumn = $fromColumns[$splitCount][0];
            $toColumn = $toColumns[$splitCount][0];

            if (!empty($fromColumn) && $fromColumn[0] !== '$') {
                $fromColumn = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($fromColumn) + $numberOfColumns);
                $formula = substr($formula, 0, $fromColumnOffset) . $fromColumn . substr($formula, $fromColumnOffset + $fromColumnLength);
            }
            if (!empty($toColumn) && $toColumn[0] !== '$') {
                $toColumn = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($toColumn) + $numberOfColumns);
                $formula = substr($formula, 0, $toColumnOffset) . $toColumn . substr($formula, $toColumnOffset + $toColumnLength);
            }
        }

        return $formula;
    }

    private function updateRowRangesAllWorksheets(string $formula, int $numberOfRows): string
    {
        $splitCount = preg_match_all(
            '/' . Calculation::CALCULATION_REGEXP_ROWRANGE_RELATIVE . '/mui',
            $formula,
            $splitRanges,
            PREG_OFFSET_CAPTURE
        );

        $fromRowLengths = array_map('strlen', array_column($splitRanges[1], 0));
        $fromRowOffsets = array_column($splitRanges[1], 1);
        $toRowLengths = array_map('strlen', array_column($splitRanges[2], 0));
        $toRowOffsets = array_column($splitRanges[2], 1);

        $fromRows = $splitRanges[1];
        $toRows = $splitRanges[2];

        while ($splitCount > 0) {
            --$splitCount;
            $fromRowLength = $fromRowLengths[$splitCount];
            $toRowLength = $toRowLengths[$splitCount];
            $fromRowOffset = $fromRowOffsets[$splitCount];
            $toRowOffset = $toRowOffsets[$splitCount];
            $fromRow = $fromRows[$splitCount][0];
            $toRow = $toRows[$splitCount][0];

            if (!empty($fromRow) && $fromRow[0] !== '$') {
                $fromRow = (int) $fromRow + $numberOfRows;
                $formula = substr($formula, 0, $fromRowOffset) . $fromRow . substr($formula, $fromRowOffset + $fromRowLength);
            }
            if (!empty($toRow) && $toRow[0] !== '$') {
                $toRow = (int) $toRow + $numberOfRows;
                $formula = substr($formula, 0, $toRowOffset) . $toRow . substr($formula, $toRowOffset + $toRowLength);
            }
        }

        return $formula;
    }

    
    private function updateCellReference(string $cellReference = 'A1', bool $includeAbsoluteReferences = false, bool $onlyAbsoluteReferences = false, ?bool $topLeft = null)
    {
        
        if (str_contains($cellReference, '!')) {
            return $cellReference;
        }
        
        if (!Coordinate::coordinateIsRange($cellReference)) {
            
            
            $cellReferenceHelper = $this->cellReferenceHelper;

            return $cellReferenceHelper->updateCellReference($cellReference, $includeAbsoluteReferences, $onlyAbsoluteReferences, $topLeft);
        }

        
        return $this->updateCellRange($cellReference, $includeAbsoluteReferences, $onlyAbsoluteReferences);
    }

    
    public function updateNamedFormulae(Spreadsheet $spreadsheet, string $oldName = '', string $newName = ''): void
    {
        if ($oldName == '') {
            return;
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            foreach ($sheet->getCoordinates(false) as $coordinate) {
                $cell = $sheet->getCell($coordinate);
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    $formula = $cell->getValueString();
                    if (str_contains($formula, $oldName)) {
                        $formula = str_replace("'" . $oldName . "'!", "'" . $newName . "'!", $formula);
                        $formula = str_replace($oldName . '!', $newName . '!', $formula);
                        $cell->setValueExplicit($formula, DataType::TYPE_FORMULA);
                    }
                }
            }
        }
    }

    private function updateDefinedNames(Worksheet $worksheet, string $beforeCellAddress, int $numberOfColumns, int $numberOfRows): void
    {
        foreach ($worksheet->getParentOrThrow()->getDefinedNames() as $definedName) {
            if ($definedName->isFormula() === false) {
                $this->updateNamedRange($definedName, $worksheet, $beforeCellAddress, $numberOfColumns, $numberOfRows);
            } else {
                $this->updateNamedFormula($definedName, $worksheet, $beforeCellAddress, $numberOfColumns, $numberOfRows);
            }
        }
    }

    private function updateNamedRange(DefinedName $definedName, Worksheet $worksheet, string $beforeCellAddress, int $numberOfColumns, int $numberOfRows): void
    {
        $cellAddress = $definedName->getValue();
        $asFormula = ($cellAddress[0] === '=');
        if ($definedName->getWorksheet() !== null && $definedName->getWorksheet()->getHashInt() === $worksheet->getHashInt()) {
            
            if ($asFormula === true) {
                $formula = $this->updateFormulaReferences($cellAddress, $beforeCellAddress, $numberOfColumns, $numberOfRows, $worksheet->getTitle(), true, true);
                $definedName->setValue($formula);
            } else {
                $definedName->setValue($this->updateCellReference(ltrim($cellAddress, '='), true));
            }
        }
    }

    private function updateNamedFormula(DefinedName $definedName, Worksheet $worksheet, string $beforeCellAddress, int $numberOfColumns, int $numberOfRows): void
    {
        if ($definedName->getWorksheet() !== null && $definedName->getWorksheet()->getHashInt() === $worksheet->getHashInt()) {
            
            $formula = $definedName->getValue();
            $formula = $this->updateFormulaReferences($formula, $beforeCellAddress, $numberOfColumns, $numberOfRows, $worksheet->getTitle(), true);
            $definedName->setValue($formula);
        }
    }

    
    private function updateCellRange(string $cellRange = 'A1:A1', bool $includeAbsoluteReferences = false, bool $onlyAbsoluteReferences = false): string
    {
        if (!Coordinate::coordinateIsRange($cellRange)) {
            throw new Exception('Only cell ranges may be passed to this method.');
        }

        
        $range = Coordinate::splitRange($cellRange);
        $ic = count($range);
        for ($i = 0; $i < $ic; ++$i) {
            $jc = count($range[$i]);
            for ($j = 0; $j < $jc; ++$j) {
                
                $cellReferenceHelper = $this->cellReferenceHelper;
                if (ctype_alpha($range[$i][$j])) {
                    $range[$i][$j] = Coordinate::coordinateFromString(
                        $cellReferenceHelper->updateCellReference($range[$i][$j] . '1', $includeAbsoluteReferences, $onlyAbsoluteReferences, null)
                    )[0];
                } elseif (ctype_digit($range[$i][$j])) {
                    $range[$i][$j] = Coordinate::coordinateFromString(
                        $cellReferenceHelper->updateCellReference('A' . $range[$i][$j], $includeAbsoluteReferences, $onlyAbsoluteReferences, null)
                    )[1];
                } else {
                    $range[$i][$j] = $cellReferenceHelper->updateCellReference($range[$i][$j], $includeAbsoluteReferences, $onlyAbsoluteReferences, null);
                }
            }
        }

        
        return Coordinate::buildRange($range);
    }

    private function clearColumnStrips(int $highestRow, int $beforeColumn, int $numberOfColumns, Worksheet $worksheet): void
    {
        $startColumnId = Coordinate::stringFromColumnIndex($beforeColumn + $numberOfColumns);
        $endColumnId = Coordinate::stringFromColumnIndex($beforeColumn);

        for ($row = 1; $row <= $highestRow - 1; ++$row) {
            for ($column = $startColumnId; $column !== $endColumnId; ++$column) {
                $coordinate = $column . $row;
                $this->clearStripCell($worksheet, $coordinate);
            }
        }
    }

    private function clearRowStrips(string $highestColumn, int $beforeColumn, int $beforeRow, int $numberOfRows, Worksheet $worksheet): void
    {
        $startColumnId = Coordinate::stringFromColumnIndex($beforeColumn);
        ++$highestColumn;

        for ($column = $startColumnId; $column !== $highestColumn; ++$column) {
            for ($row = $beforeRow + $numberOfRows; $row <= $beforeRow - 1; ++$row) {
                $coordinate = $column . $row;
                $this->clearStripCell($worksheet, $coordinate);
            }
        }
    }

    private function clearStripCell(Worksheet $worksheet, string $coordinate): void
    {
        $worksheet->removeConditionalStyles($coordinate);
        $worksheet->setHyperlink($coordinate);
        $worksheet->setDataValidation($coordinate);
        $worksheet->removeComment($coordinate);

        if ($worksheet->cellExists($coordinate)) {
            $worksheet->getCell($coordinate)->setValueExplicit(null, DataType::TYPE_NULL);
            $worksheet->getCell($coordinate)->setXfIndex(0);
        }
    }

    private function adjustAutoFilter(Worksheet $worksheet, string $beforeCellAddress, int $numberOfColumns): void
    {
        $autoFilter = $worksheet->getAutoFilter();
        $autoFilterRange = $autoFilter->getRange();
        if (!empty($autoFilterRange)) {
            if ($numberOfColumns !== 0) {
                $autoFilterColumns = $autoFilter->getColumns();
                if (count($autoFilterColumns) > 0) {
                    $column = '';
                    $row = 0;
                    sscanf($beforeCellAddress, '%[A-Z]%d', $column, $row);
                    $columnIndex = Coordinate::columnIndexFromString((string) $column);
                    [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($autoFilterRange);
                    if ($columnIndex <= $rangeEnd[0]) {
                        if ($numberOfColumns < 0) {
                            $this->adjustAutoFilterDeleteRules($columnIndex, $numberOfColumns, $autoFilterColumns, $autoFilter);
                        }
                        $startCol = ($columnIndex > $rangeStart[0]) ? $columnIndex : $rangeStart[0];

                        
                        if ($numberOfColumns > 0) {
                            $this->adjustAutoFilterInsert($startCol, $numberOfColumns, $rangeEnd[0], $autoFilter);
                        } else {
                            $this->adjustAutoFilterDelete($startCol, $numberOfColumns, $rangeEnd[0], $autoFilter);
                        }
                    }
                }
            }

            $worksheet->setAutoFilter(
                $this->updateCellReference($autoFilterRange)
            );
        }
    }

    private function adjustAutoFilterDeleteRules(int $columnIndex, int $numberOfColumns, array $autoFilterColumns, AutoFilter $autoFilter): void
    {
        
        
        $deleteColumn = $columnIndex + $numberOfColumns - 1;
        $deleteCount = abs($numberOfColumns);

        for ($i = 1; $i <= $deleteCount; ++$i) {
            $columnName = Coordinate::stringFromColumnIndex($deleteColumn + 1);
            if (isset($autoFilterColumns[$columnName])) {
                $autoFilter->clearColumn($columnName);
            }
            ++$deleteColumn;
        }
    }

    private function adjustAutoFilterInsert(int $startCol, int $numberOfColumns, int $rangeEnd, AutoFilter $autoFilter): void
    {
        $startColRef = $startCol;
        $endColRef = $rangeEnd;
        $toColRef = $rangeEnd + $numberOfColumns;

        do {
            $autoFilter->shiftColumn(Coordinate::stringFromColumnIndex($endColRef), Coordinate::stringFromColumnIndex($toColRef));
            --$endColRef;
            --$toColRef;
        } while ($startColRef <= $endColRef);
    }

    private function adjustAutoFilterDelete(int $startCol, int $numberOfColumns, int $rangeEnd, AutoFilter $autoFilter): void
    {
        
        $startColID = Coordinate::stringFromColumnIndex($startCol);
        $toColID = Coordinate::stringFromColumnIndex($startCol + $numberOfColumns);
        $endColID = Coordinate::stringFromColumnIndex($rangeEnd + 1);

        do {
            $autoFilter->shiftColumn($startColID, $toColID);
            ++$toColID;
            ++$startColID; 
        } while ($startColID !== $endColID); 
    }

    private function adjustTable(Worksheet $worksheet, string $beforeCellAddress, int $numberOfColumns): void
    {
        $tableCollection = $worksheet->getTableCollection();

        foreach ($tableCollection as $table) {
            $tableRange = $table->getRange();
            if (!empty($tableRange)) {
                if ($numberOfColumns !== 0) {
                    $tableColumns = $table->getColumns();
                    if (count($tableColumns) > 0) {
                        $column = '';
                        $row = 0;
                        sscanf($beforeCellAddress, '%[A-Z]%d', $column, $row);
                        $columnIndex = Coordinate::columnIndexFromString((string) $column);
                        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($tableRange);
                        if ($columnIndex <= $rangeEnd[0]) {
                            if ($numberOfColumns < 0) {
                                $this->adjustTableDeleteRules($columnIndex, $numberOfColumns, $tableColumns, $table);
                            }
                            $startCol = ($columnIndex > $rangeStart[0]) ? $columnIndex : $rangeStart[0];

                            
                            if ($numberOfColumns > 0) {
                                $this->adjustTableInsert($startCol, $numberOfColumns, $rangeEnd[0], $table);
                            } else {
                                $this->adjustTableDelete($startCol, $numberOfColumns, $rangeEnd[0], $table);
                            }
                        }
                    }
                }

                $table->setRange($this->updateCellReference($tableRange));
            }
        }
    }

    private function adjustTableDeleteRules(int $columnIndex, int $numberOfColumns, array $tableColumns, Table $table): void
    {
        
        
        $deleteColumn = $columnIndex + $numberOfColumns - 1;
        $deleteCount = abs($numberOfColumns);

        for ($i = 1; $i <= $deleteCount; ++$i) {
            $columnName = Coordinate::stringFromColumnIndex($deleteColumn + 1);
            if (isset($tableColumns[$columnName])) {
                $table->clearColumn($columnName);
            }
            ++$deleteColumn;
        }
    }

    private function adjustTableInsert(int $startCol, int $numberOfColumns, int $rangeEnd, Table $table): void
    {
        $startColRef = $startCol;
        $endColRef = $rangeEnd;
        $toColRef = $rangeEnd + $numberOfColumns;

        do {
            $table->shiftColumn(Coordinate::stringFromColumnIndex($endColRef), Coordinate::stringFromColumnIndex($toColRef));
            --$endColRef;
            --$toColRef;
        } while ($startColRef <= $endColRef);
    }

    private function adjustTableDelete(int $startCol, int $numberOfColumns, int $rangeEnd, Table $table): void
    {
        
        $startColID = Coordinate::stringFromColumnIndex($startCol);
        $toColID = Coordinate::stringFromColumnIndex($startCol + $numberOfColumns);
        $endColID = Coordinate::stringFromColumnIndex($rangeEnd + 1);

        do {
            $table->shiftColumn($startColID, $toColID);
            ++$toColID;
            ++$startColID; 
        } while ($startColID !== $endColID); 
    }

    private function duplicateStylesByColumn(Worksheet $worksheet, int $beforeColumn, int $beforeRow, int $highestRow, int $numberOfColumns): void
    {
        $beforeColumnName = Coordinate::stringFromColumnIndex($beforeColumn - 1);
        for ($i = $beforeRow; $i <= $highestRow - 1; ++$i) {
            
            $coordinate = $beforeColumnName . $i;
            if ($worksheet->cellExists($coordinate)) {
                $xfIndex = $worksheet->getCell($coordinate)->getXfIndex();
                for ($j = $beforeColumn; $j <= $beforeColumn - 1 + $numberOfColumns; ++$j) {
                    if (!empty($xfIndex) || $worksheet->cellExists([$j, $i])) {
                        $worksheet->getCell([$j, $i])->setXfIndex($xfIndex);
                    }
                }
            }
        }
    }

    private function duplicateStylesByRow(Worksheet $worksheet, int $beforeColumn, int $beforeRow, string $highestColumn, int $numberOfRows): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        for ($i = $beforeColumn; $i <= $highestColumnIndex; ++$i) {
            
            $coordinate = Coordinate::stringFromColumnIndex($i) . ($beforeRow - 1);
            if ($worksheet->cellExists($coordinate)) {
                $xfIndex = $worksheet->getCell($coordinate)->getXfIndex();
                for ($j = $beforeRow; $j <= $beforeRow - 1 + $numberOfRows; ++$j) {
                    if (!empty($xfIndex) || $worksheet->cellExists([$i, $j])) {
                        $worksheet->getCell(Coordinate::stringFromColumnIndex($i) . $j)->setXfIndex($xfIndex);
                    }
                }
            }
        }
    }

    
    final public function __clone()
    {
        throw new Exception('Cloning a Singleton is not allowed!');
    }
}
