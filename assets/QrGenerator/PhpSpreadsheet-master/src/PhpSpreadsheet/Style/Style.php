<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Style extends Supervisor
{
    
    protected Font $font;

    
    protected Fill $fill;

    
    protected Borders $borders;

    
    protected Alignment $alignment;

    
    protected NumberFormat $numberFormat;

    
    protected Protection $protection;

    
    protected int $index;

    
    protected bool $quotePrefix = false;

    
    private static ?array $cachedStyles = null;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        parent::__construct($isSupervisor);

        
        $this->font = new Font($isSupervisor, $isConditional);
        $this->fill = new Fill($isSupervisor, $isConditional);
        $this->borders = new Borders($isSupervisor, $isConditional);
        $this->alignment = new Alignment($isSupervisor, $isConditional);
        $this->numberFormat = new NumberFormat($isSupervisor, $isConditional);
        $this->protection = new Protection($isSupervisor, $isConditional);

        
        if ($isSupervisor) {
            $this->font->bindParent($this);
            $this->fill->bindParent($this);
            $this->borders->bindParent($this);
            $this->alignment->bindParent($this);
            $this->numberFormat->bindParent($this);
            $this->protection->bindParent($this);
        }
    }

    
    public function getSharedComponent(): self
    {
        $activeSheet = $this->getActiveSheet();
        $selectedCell = Functions::trimSheetFromCellReference($this->getActiveCell()); 

        if ($activeSheet->cellExists($selectedCell)) {
            $xfIndex = $activeSheet->getCell($selectedCell)->getXfIndex();
        } else {
            $xfIndex = 0;
        }

        return $activeSheet->getParentOrThrow()->getCellXfByIndex($xfIndex);
    }

    
    public function getParent(): Spreadsheet
    {
        return $this->getActiveSheet()->getParentOrThrow();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['quotePrefix' => $array];
    }

    
    public function applyFromArray(array $styleArray, bool $advancedBorders = true): static
    {
        if ($this->isSupervisor) {
            $pRange = $this->getSelectedCells();

            
            $pRange = strtoupper($pRange);
            if (str_contains($pRange, '!')) {
                $pRangeWorksheet = StringHelper::strToUpper(substr($pRange, 0, (int) strrpos($pRange, '!')));
                $pRangeWorksheet = Worksheet::unApostrophizeTitle($pRangeWorksheet);
                if ($pRangeWorksheet !== '' && StringHelper::strToUpper($this->getActiveSheet()->getTitle()) !== $pRangeWorksheet) {
                    throw new Exception('Invalid Worksheet for specified Range');
                }
                $pRange = strtoupper(Functions::trimSheetFromCellReference($pRange));
            }

            
            if (!str_contains($pRange, ':')) {
                $rangeA = $pRange;
                $rangeB = $pRange;
            } else {
                [$rangeA, $rangeB] = explode(':', $pRange);
            }

            
            $rangeStart = Coordinate::coordinateFromString($rangeA);
            $rangeEnd = Coordinate::coordinateFromString($rangeB);
            $rangeStartIndexes = Coordinate::indexesFromString($rangeA);
            $rangeEndIndexes = Coordinate::indexesFromString($rangeB);

            $columnStart = $rangeStart[0];
            $columnEnd = $rangeEnd[0];

            
            if ($rangeStartIndexes[0] > $rangeEndIndexes[0] && $rangeStartIndexes[1] > $rangeEndIndexes[1]) {
                $tmp = $rangeStartIndexes;
                $rangeStartIndexes = $rangeEndIndexes;
                $rangeEndIndexes = $tmp;
            }

            
            if ($advancedBorders && isset($styleArray['borders'])) {
                
                
                if (isset($styleArray['borders']['allBorders'])) {
                    foreach (['outline', 'inside'] as $component) {
                        if (!isset($styleArray['borders'][$component])) {
                            $styleArray['borders'][$component] = $styleArray['borders']['allBorders'];
                        }
                    }
                    unset($styleArray['borders']['allBorders']); 
                }
                
                
                if (isset($styleArray['borders']['outline'])) {
                    foreach (['top', 'right', 'bottom', 'left'] as $component) {
                        if (!isset($styleArray['borders'][$component])) {
                            $styleArray['borders'][$component] = $styleArray['borders']['outline'];
                        }
                    }
                    unset($styleArray['borders']['outline']); 
                }
                
                
                if (isset($styleArray['borders']['inside'])) {
                    foreach (['vertical', 'horizontal'] as $component) {
                        if (!isset($styleArray['borders'][$component])) {
                            $styleArray['borders'][$component] = $styleArray['borders']['inside'];
                        }
                    }
                    unset($styleArray['borders']['inside']); 
                }
                
                $xMax = min($rangeEndIndexes[0] - $rangeStartIndexes[0] + 1, 3);
                $yMax = min($rangeEndIndexes[1] - $rangeStartIndexes[1] + 1, 3);

                
                for ($x = 1; $x <= $xMax; ++$x) {
                    
                    $colStart = ($x == 3)
                        ? Coordinate::stringFromColumnIndex($rangeEndIndexes[0])
                        : Coordinate::stringFromColumnIndex($rangeStartIndexes[0] + $x - 1);
                    
                    $colEnd = ($x == 1)
                        ? Coordinate::stringFromColumnIndex($rangeStartIndexes[0])
                        : Coordinate::stringFromColumnIndex($rangeEndIndexes[0] - $xMax + $x);

                    for ($y = 1; $y <= $yMax; ++$y) {
                        
                        $edges = [];
                        if ($x == 1) {
                            
                            $edges[] = 'left';
                        }
                        if ($x == $xMax) {
                            
                            $edges[] = 'right';
                        }
                        if ($y == 1) {
                            
                            $edges[] = 'top';
                        }
                        if ($y == $yMax) {
                            
                            $edges[] = 'bottom';
                        }

                        
                        $rowStart = ($y == 3)
                            ? $rangeEndIndexes[1] : $rangeStartIndexes[1] + $y - 1;

                        
                        $rowEnd = ($y == 1)
                            ? $rangeStartIndexes[1] : $rangeEndIndexes[1] - $yMax + $y;

                        
                        $range = $colStart . $rowStart . ':' . $colEnd . $rowEnd;

                        
                        $regionStyles = $styleArray;
                        unset($regionStyles['borders']['inside']);

                        
                        $innerEdges = array_diff(['top', 'right', 'bottom', 'left'], $edges);

                        
                        foreach ($innerEdges as $innerEdge) {
                            switch ($innerEdge) {
                                case 'top':
                                case 'bottom':
                                    
                                    if (isset($styleArray['borders']['horizontal'])) {
                                        $regionStyles['borders'][$innerEdge] = $styleArray['borders']['horizontal'];
                                    } else {
                                        unset($regionStyles['borders'][$innerEdge]);
                                    }

                                    break;
                                case 'left':
                                case 'right':
                                    
                                    if (isset($styleArray['borders']['vertical'])) {
                                        $regionStyles['borders'][$innerEdge] = $styleArray['borders']['vertical'];
                                    } else {
                                        unset($regionStyles['borders'][$innerEdge]);
                                    }

                                    break;
                            }
                        }

                        
                        $this->getActiveSheet()->getStyle($range)->applyFromArray($regionStyles, false);
                    }
                }

                
                $this->getActiveSheet()->getStyle($pRange);

                return $this;
            }

            
            
            if (preg_match('/^[A-Z]+1:[A-Z]+1048576$/', $pRange)) {
                $selectionType = 'COLUMN';

                
                self::$cachedStyles = ['hashByObjId' => [], 'styleByHash' => []];
            } elseif (preg_match('/^A\d+:XFD\d+$/', $pRange)) {
                $selectionType = 'ROW';

                
                self::$cachedStyles = ['hashByObjId' => [], 'styleByHash' => []];
            } else {
                $selectionType = 'CELL';
            }

            
            $oldXfIndexes = $this->getOldXfIndexes($selectionType, $rangeStartIndexes, $rangeEndIndexes, $columnStart, $columnEnd, $styleArray);

            
            $workbook = $this->getActiveSheet()->getParentOrThrow();
            $newXfIndexes = [];
            foreach ($oldXfIndexes as $oldXfIndex => $dummy) {
                $style = $workbook->getCellXfByIndex($oldXfIndex);

                
                if (self::$cachedStyles === null) {
                    
                    $newStyle = clone $style;
                    $newStyle->applyFromArray($styleArray);

                    
                    $existingStyle = $workbook->getCellXfByHashCode($newStyle->getHashCode());
                } else {
                    
                    
                    $objId = spl_object_id($style);

                    
                    $styleHash = self::$cachedStyles['hashByObjId'][$objId] ?? null;
                    if ($styleHash === null) {
                        
                        $styleHash = self::$cachedStyles['hashByObjId'][$objId] = $style->getHashCode();
                    }

                    
                    $existingStyle = self::$cachedStyles['styleByHash'][$styleHash] ?? null;

                    if (!$existingStyle) {
                        
                        $newStyle = clone $style;
                        $newStyle->applyFromArray($styleArray);

                        
                        $existingStyle = $workbook->getCellXfByHashCode($newStyle->getHashCode());

                        
                        self::$cachedStyles['styleByHash'][$styleHash] = $existingStyle instanceof self ? $existingStyle : $newStyle;
                    }
                }

                if ($existingStyle) {
                    
                    $newXfIndexes[$oldXfIndex] = $existingStyle->getIndex();
                } else {
                    if (!isset($newStyle)) {
                        
                        
                        
                        
                        $newStyle = clone $style;
                        $newStyle->applyFromArray($styleArray);
                        
                    }

                    
                    $workbook->addCellXf($newStyle);
                    $newXfIndexes[$oldXfIndex] = $newStyle->getIndex();
                }
            }

            
            switch ($selectionType) {
                case 'COLUMN':
                    for ($col = $rangeStartIndexes[0]; $col <= $rangeEndIndexes[0]; ++$col) {
                        $columnDimension = $this->getActiveSheet()->getColumnDimensionByColumn($col);
                        $oldXfIndex = $columnDimension->getXfIndex();
                        $columnDimension->setXfIndex($newXfIndexes[$oldXfIndex]);
                    }

                    
                    self::$cachedStyles = null;

                    break;
                case 'ROW':
                    for ($row = $rangeStartIndexes[1]; $row <= $rangeEndIndexes[1]; ++$row) {
                        $rowDimension = $this->getActiveSheet()->getRowDimension($row);
                        
                        $oldXfIndex = $rowDimension->getXfIndex() ?? 0;
                        $rowDimension->setXfIndex($newXfIndexes[$oldXfIndex]);
                    }

                    
                    self::$cachedStyles = null;

                    break;
                case 'CELL':
                    for ($col = $rangeStartIndexes[0]; $col <= $rangeEndIndexes[0]; ++$col) {
                        for ($row = $rangeStartIndexes[1]; $row <= $rangeEndIndexes[1]; ++$row) {
                            $cell = $this->getActiveSheet()->getCell([$col, $row]);
                            $oldXfIndex = $cell->getXfIndex();
                            $cell->setXfIndex($newXfIndexes[$oldXfIndex]);
                        }
                    }

                    break;
            }
        } else {
            
            if (isset($styleArray['fill'])) {
                $this->getFill()->applyFromArray($styleArray['fill']);
            }
            if (isset($styleArray['font'])) {
                $this->getFont()->applyFromArray($styleArray['font']);
            }
            if (isset($styleArray['borders'])) {
                $this->getBorders()->applyFromArray($styleArray['borders']);
            }
            if (isset($styleArray['alignment'])) {
                $this->getAlignment()->applyFromArray($styleArray['alignment']);
            }
            if (isset($styleArray['numberFormat'])) {
                $this->getNumberFormat()->applyFromArray($styleArray['numberFormat']);
            }
            if (isset($styleArray['protection'])) {
                $this->getProtection()->applyFromArray($styleArray['protection']);
            }
            if (isset($styleArray['quotePrefix'])) {
                $this->quotePrefix = $styleArray['quotePrefix'];
            }
        }

        return $this;
    }

    private function getOldXfIndexes(string $selectionType, array $rangeStart, array $rangeEnd, string $columnStart, string $columnEnd, array $styleArray): array
    {
        $oldXfIndexes = [];
        switch ($selectionType) {
            case 'COLUMN':
                for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
                    $oldXfIndexes[$this->getActiveSheet()->getColumnDimensionByColumn($col)->getXfIndex()] = true;
                }
                foreach ($this->getActiveSheet()->getColumnIterator($columnStart, $columnEnd) as $columnIterator) {
                    $cellIterator = $columnIterator->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true);
                    foreach ($cellIterator as $columnCell) {
                        $columnCell->getStyle()
                            ->applyFromArray($styleArray);
                    }
                }

                break;
            case 'ROW':
                for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
                    if ($this->getActiveSheet()->getRowDimension($row)->getXfIndex() === null) {
                        $oldXfIndexes[0] = true; 
                    } else {
                        $oldXfIndexes[$this->getActiveSheet()->getRowDimension($row)->getXfIndex()] = true;
                    }
                }
                foreach ($this->getActiveSheet()->getRowIterator((int) $rangeStart[1], (int) $rangeEnd[1]) as $rowIterator) {
                    $cellIterator = $rowIterator->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true);
                    foreach ($cellIterator as $rowCell) {
                        $rowCell->getStyle()
                            ->applyFromArray($styleArray);
                    }
                }

                break;
            case 'CELL':
                for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
                    for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
                        $oldXfIndexes[$this->getActiveSheet()->getCell([$col, $row])->getXfIndex()] = true;
                    }
                }

                break;
        }

        return $oldXfIndexes;
    }

    
    public function getFill(): Fill
    {
        return $this->fill;
    }

    
    public function getFont(): Font
    {
        return $this->font;
    }

    
    public function setFont(Font $font): static
    {
        $this->font = $font;

        return $this;
    }

    
    public function getBorders(): Borders
    {
        return $this->borders;
    }

    
    public function getAlignment(): Alignment
    {
        return $this->alignment;
    }

    
    public function getNumberFormat(): NumberFormat
    {
        return $this->numberFormat;
    }

    
    public function getConditionalStyles(): array
    {
        return $this->getActiveSheet()->getConditionalStyles($this->getActiveCell());
    }

    
    public function setConditionalStyles(array $conditionalStyleArray): static
    {
        $this->getActiveSheet()->setConditionalStyles($this->getSelectedCells(), $conditionalStyleArray);

        return $this;
    }

    
    public function getProtection(): Protection
    {
        return $this->protection;
    }

    
    public function getQuotePrefix(): bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getQuotePrefix();
        }

        return $this->quotePrefix;
    }

    
    public function setQuotePrefix(bool $quotePrefix): static
    {
        if ($quotePrefix == '') {
            $quotePrefix = false;
        }
        if ($this->isSupervisor) {
            $styleArray = ['quotePrefix' => $quotePrefix];
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->quotePrefix = (bool) $quotePrefix;
        }

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->fill->getHashCode()
            . $this->font->getHashCode()
            . $this->borders->getHashCode()
            . $this->alignment->getHashCode()
            . $this->numberFormat->getHashCode()
            . $this->protection->getHashCode()
            . ($this->quotePrefix ? 't' : 'f')
            . __CLASS__
        );
    }

    
    public function getIndex(): int
    {
        return $this->index;
    }

    
    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'alignment', $this->getAlignment());
        $this->exportArray2($exportedArray, 'borders', $this->getBorders());
        $this->exportArray2($exportedArray, 'fill', $this->getFill());
        $this->exportArray2($exportedArray, 'font', $this->getFont());
        $this->exportArray2($exportedArray, 'numberFormat', $this->getNumberFormat());
        $this->exportArray2($exportedArray, 'protection', $this->getProtection());
        $this->exportArray2($exportedArray, 'quotePrefix', $this->getQuotePrefix());

        return $exportedArray;
    }
}
