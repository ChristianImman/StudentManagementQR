<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\ReferenceHelper;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Slk extends BaseReader
{
    
    private int $sheetIndex = 0;

    
    private array $formats = [];

    
    private int $format = 0;

    
    private array $fonts = [];

    
    private int $fontcount = 0;

    
    public function __construct()
    {
        parent::__construct();
    }

    
    public function canRead(string $filename): bool
    {
        try {
            $this->openFile($filename);
        } catch (ReaderException) {
            return false;
        }

        
        $data = (string) fread($this->fileHandle, 2048);

        
        $delimiterCount = substr_count($data, ';');
        $hasDelimiter = $delimiterCount > 0;

        
        $lines = explode("\n", $data);
        $hasId = str_starts_with($lines[0], 'ID;P');

        fclose($this->fileHandle);

        return $hasDelimiter && $hasId;
    }

    private function canReadOrBust(string $filename): void
    {
        if (!$this->canRead($filename)) {
            throw new ReaderException($filename . ' is an Invalid SYLK file.');
        }
        $this->openFile($filename);
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        
        $this->canReadOrBust($filename);
        $fileHandle = $this->fileHandle;
        rewind($fileHandle);

        $worksheetInfo = [['worksheetName' => basename($filename, '.slk')]];

        
        $rowIndex = 0;
        $columnIndex = 0;
        while (($rowData = fgets($fileHandle)) !== false) {
            $columnIndex = 0;

            
            $rowData = StringHelper::SYLKtoUTF8($rowData);

            
            
            $rowData = explode("\t", str_replace('¤', ';', str_replace(';', "\t", str_replace(';;', '¤', rtrim($rowData)))));

            $dataType = array_shift($rowData);
            if ($dataType == 'B') {
                foreach ($rowData as $rowDatum) {
                    switch ($rowDatum[0]) {
                        case 'X':
                            $columnIndex = (int) substr($rowDatum, 1) - 1;

                            break;
                        case 'Y':
                            $rowIndex = (int) substr($rowDatum, 1);

                            break;
                    }
                }

                break;
            }
        }

        $worksheetInfo[0]['lastColumnIndex'] = $columnIndex;
        $worksheetInfo[0]['totalRows'] = $rowIndex;
        $worksheetInfo[0]['lastColumnLetter'] = Coordinate::stringFromColumnIndex($worksheetInfo[0]['lastColumnIndex'] + 1);
        $worksheetInfo[0]['totalColumns'] = $worksheetInfo[0]['lastColumnIndex'] + 1;
        $worksheetInfo[0]['sheetState'] = Worksheet::SHEETSTATE_VISIBLE;

        
        fclose($fileHandle);

        return $worksheetInfo;
    }

    
    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);

        
        return $this->loadIntoExisting($filename, $spreadsheet);
    }

    private const COLOR_ARRAY = [
        'FF00FFFF', 
        'FF000000', 
        'FFFFFFFF', 
        'FFFF0000', 
        'FF00FF00', 
        'FF0000FF', 
        'FFFFFF00', 
        'FFFF00FF', 
    ];

    private const FONT_STYLE_MAPPINGS = [
        'B' => 'bold',
        'I' => 'italic',
        'U' => 'underline',
    ];

    private function processFormula(string $rowDatum, bool &$hasCalculatedValue, string &$cellDataFormula, string $row, string $column): void
    {
        $cellDataFormula = '=' . substr($rowDatum, 1);
        
        $temp = explode('"', $cellDataFormula);
        $key = false;
        foreach ($temp as &$value) {
            
            $key = $key === false;
            if ($key) {
                preg_match_all('/(R(\[?-?\d*\]?))(C(\[?-?\d*\]?))/', $value, $cellReferences, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
                
                
                
                $cellReferences = array_reverse($cellReferences);
                
                
                foreach ($cellReferences as $cellReference) {
                    $rowReference = $cellReference[2][0];
                    
                    if ($rowReference == '') {
                        $rowReference = $row;
                    }
                    
                    if ($rowReference[0] == '[') {
                        $rowReference = (int) $row + (int) trim($rowReference, '[]');
                    }
                    $columnReference = $cellReference[4][0];
                    
                    if ($columnReference == '') {
                        $columnReference = $column;
                    }
                    
                    if ($columnReference[0] == '[') {
                        $columnReference = (int) $column + (int) trim($columnReference, '[]');
                    }
                    $A1CellReference = Coordinate::stringFromColumnIndex((int) $columnReference) . $rowReference;

                    $value = substr_replace($value, $A1CellReference, $cellReference[0][1], strlen($cellReference[0][0]));
                }
            }
        }
        unset($value);
        
        $cellDataFormula = implode('"', $temp);
        $hasCalculatedValue = true;
    }

    
    private function processCRecord(array $rowData, Spreadsheet &$spreadsheet, string &$row, string &$column): void
    {
        
        $hasCalculatedValue = false;
        $tryNumeric = false;
        $cellDataFormula = $cellData = '';
        $sharedColumn = $sharedRow = -1;
        $sharedFormula = false;
        foreach ($rowData as $rowDatum) {
            
            switch ($rowDatum[0]) {
                case 'X':
                    $column = substr($rowDatum, 1);

                    break;
                case 'Y':
                    $row = substr($rowDatum, 1);

                    break;
                case 'K':
                    $cellData = substr($rowDatum, 1);
                    $tryNumeric = is_numeric($cellData);

                    break;
                case 'E':
                    $this->processFormula($rowDatum, $hasCalculatedValue, $cellDataFormula, $row, $column);

                    break;
                case 'A':
                    $comment = substr($rowDatum, 1);
                    $columnLetter = Coordinate::stringFromColumnIndex((int) $column);
                    $spreadsheet->getActiveSheet()
                        ->getComment("$columnLetter$row")
                        ->getText()
                        ->createText($comment);

                    break;
                case 'C':
                    $sharedColumn = (int) substr($rowDatum, 1);

                    break;
                case 'R':
                    $sharedRow = (int) substr($rowDatum, 1);

                    break;
                case 'S':
                    $sharedFormula = true;

                    break;
            }
        }
        if ($sharedFormula === true && $sharedRow >= 0 && $sharedColumn >= 0) {
            $thisCoordinate = Coordinate::stringFromColumnIndex((int) $column) . $row;
            $sharedCoordinate = Coordinate::stringFromColumnIndex($sharedColumn) . $sharedRow;
            
            $formula = $spreadsheet->getActiveSheet()->getCell($sharedCoordinate)->getValue();
            $spreadsheet->getActiveSheet()->getCell($thisCoordinate)->setValue($formula);
            $referenceHelper = ReferenceHelper::getInstance();
            $newFormula = $referenceHelper->updateFormulaReferences($formula, 'A1', (int) $column - $sharedColumn, (int) $row - $sharedRow, '', true, false);
            $spreadsheet->getActiveSheet()->getCell($thisCoordinate)->setValue($newFormula);
            
            
            $cellData = Calculation::unwrapResult($cellData);
            $spreadsheet->getActiveSheet()->getCell($thisCoordinate)->setCalculatedValue($cellData, $tryNumeric);

            return;
        }
        $columnLetter = Coordinate::stringFromColumnIndex((int) $column);
        
        $cellData = Calculation::unwrapResult($cellData);

        
        $this->processCFinal($spreadsheet, $hasCalculatedValue, $cellDataFormula, $cellData, "$columnLetter$row", $tryNumeric);
    }

    private function processCFinal(Spreadsheet &$spreadsheet, bool $hasCalculatedValue, string $cellDataFormula, string $cellData, string $coordinate, bool $tryNumeric): void
    {
        
        $spreadsheet->getActiveSheet()->getCell($coordinate)->setValue(($hasCalculatedValue) ? $cellDataFormula : $cellData);
        if ($hasCalculatedValue) {
            $cellData = Calculation::unwrapResult($cellData);
            $spreadsheet->getActiveSheet()->getCell($coordinate)->setCalculatedValue($cellData, $tryNumeric);
        }
    }

    
    private function processFRecord(array $rowData, Spreadsheet &$spreadsheet, string &$row, string &$column): void
    {
        
        $formatStyle = $columnWidth = '';
        $startCol = $endCol = '';
        $fontStyle = '';
        $styleData = [];
        foreach ($rowData as $rowDatum) {
            
            switch ($rowDatum[0]) {
                case 'C':
                case 'X':
                    $column = substr($rowDatum, 1);

                    break;
                case 'R':
                case 'Y':
                    $row = substr($rowDatum, 1);

                    break;
                case 'P':
                    $formatStyle = $rowDatum;

                    break;
                case 'W':
                    [$startCol, $endCol, $columnWidth] = explode(' ', substr($rowDatum, 1));

                    break;
                case 'S':
                    $this->styleSettings($rowDatum, $styleData, $fontStyle);

                    break;
            }
        }
        
        $this->addFormats($spreadsheet, $formatStyle, $row, $column);
        $this->addFonts($spreadsheet, $fontStyle, $row, $column);
        $this->addStyle($spreadsheet, $styleData, $row, $column);
        $this->addWidth($spreadsheet, $columnWidth, $startCol, $endCol);
    }

    private const STYLE_SETTINGS_FONT = ['D' => 'bold', 'I' => 'italic'];

    private const STYLE_SETTINGS_BORDER = [
        'B' => 'bottom',
        'L' => 'left',
        'R' => 'right',
        'T' => 'top',
    ];

    
    private function styleSettings(string $rowDatum, array &$styleData, string &$fontStyle): void
    {
        $styleSettings = substr($rowDatum, 1);
        $iMax = strlen($styleSettings);
        for ($i = 0; $i < $iMax; ++$i) {
            $char = $styleSettings[$i];
            if (array_key_exists($char, self::STYLE_SETTINGS_FONT)) {
                $styleData['font'][self::STYLE_SETTINGS_FONT[$char]] = true;
            } elseif (array_key_exists($char, self::STYLE_SETTINGS_BORDER)) {
                $styleData['borders'][self::STYLE_SETTINGS_BORDER[$char]]['borderStyle'] = Border::BORDER_THIN; /
    private function addStyle(Spreadsheet &$spreadsheet, array $styleData, string $row, string $column): void
    {
        if ((!empty($styleData)) && $column > '' && $row > '') {
            $columnLetter = Coordinate::stringFromColumnIndex((int) $column);
            $spreadsheet->getActiveSheet()->getStyle($columnLetter . $row)->applyFromArray($styleData);
        }
    }

    private function addWidth(Spreadsheet $spreadsheet, string $columnWidth, string $startCol, string $endCol): void
    {
        if ($columnWidth > '') {
            if ($startCol == $endCol) {
                $startCol = Coordinate::stringFromColumnIndex((int) $startCol);
                $spreadsheet->getActiveSheet()->getColumnDimension($startCol)->setWidth((float) $columnWidth);
            } else {
                $startCol = Coordinate::stringFromColumnIndex((int) $startCol);
                $endCol = Coordinate::stringFromColumnIndex((int) $endCol);
                $spreadsheet->getActiveSheet()->getColumnDimension($startCol)->setWidth((float) $columnWidth);
                do {
                    
                    $spreadsheet->getActiveSheet()->getColumnDimension((string) ++$startCol)->setWidth((float) $columnWidth);
                } while ($startCol !== $endCol);
            }
        }
    }

    
    private function processPRecord(array $rowData, Spreadsheet &$spreadsheet): void
    {
        
        $formatArray = [];
        $fromFormats = ['\-', '\ '];
        $toFormats = ['-', ' '];
        foreach ($rowData as $rowDatum) {
            switch ($rowDatum[0]) {
                case 'P':
                    $formatArray['numberFormat']['formatCode'] = str_replace($fromFormats, $toFormats, substr($rowDatum, 1));

                    break;
                case 'E':
                case 'F':
                    $formatArray['font']['name'] = substr($rowDatum, 1);

                    break;
                case 'M':
                    $formatArray['font']['size'] = ((float) substr($rowDatum, 1)) / 20;

                    break;
                case 'L':
                    
                    $this->processPColors($rowDatum, $formatArray);

                    break;
                case 'S':
                    $this->processPFontStyles($rowDatum, $formatArray);

                    break;
            }
        }
        $this->processPFinal($spreadsheet, $formatArray);
    }

    
    private function processPColors(string $rowDatum, array &$formatArray): void
    {
        if (preg_match('/L([1-9]\d*)/', $rowDatum, $matches)) {
            $fontColor = ((int) $matches[1]) % 8;
            $formatArray['font']['color']['argb'] = self::COLOR_ARRAY[$fontColor];
        }
    }

    
    private function processPFontStyles(string $rowDatum, array &$formatArray): void
    {
        $styleSettings = substr($rowDatum, 1);
        $iMax = strlen($styleSettings);
        for ($i = 0; $i < $iMax; ++$i) {
            if (array_key_exists($styleSettings[$i], self::FONT_STYLE_MAPPINGS)) {
                $formatArray['font'][self::FONT_STYLE_MAPPINGS[$styleSettings[$i]]] = true;
            }
        }
    }

    
    private function processPFinal(Spreadsheet &$spreadsheet, array $formatArray): void
    {
        if (array_key_exists('numberFormat', $formatArray)) {
            $this->formats['P' . $this->format] = $formatArray;
            ++$this->format;
        } elseif (array_key_exists('font', $formatArray)) {
            ++$this->fontcount;
            $this->fonts[$this->fontcount] = $formatArray;
            if ($this->fontcount === 1) {
                $spreadsheet->getDefaultStyle()->applyFromArray($formatArray);
            }
        }
    }

    
    public function loadIntoExisting(string $filename, Spreadsheet $spreadsheet): Spreadsheet
    {
        
        $this->canReadOrBust($filename);
        $fileHandle = $this->fileHandle;
        rewind($fileHandle);

        
        while ($spreadsheet->getSheetCount() <= $this->sheetIndex) {
            $spreadsheet->createSheet();
        }
        $spreadsheet->setActiveSheetIndex($this->sheetIndex);
        $spreadsheet->getActiveSheet()->setTitle(substr(basename($filename, '.slk'), 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH));

        
        $column = $row = '';

        
        while (($rowDataTxt = fgets($fileHandle)) !== false) {
            
            $rowDataTxt = StringHelper::SYLKtoUTF8($rowDataTxt);

            
            
            $rowData = explode("\t", str_replace('¤', ';', str_replace(';', "\t", str_replace(';;', '¤', rtrim($rowDataTxt)))));

            $dataType = array_shift($rowData);
            if ($dataType == 'P') {
                
                $this->processPRecord($rowData, $spreadsheet);
            } elseif ($dataType == 'C') {
                
                $this->processCRecord($rowData, $spreadsheet, $row, $column);
            } elseif ($dataType == 'F') {
                
                $this->processFRecord($rowData, $spreadsheet, $row, $column);
            } else {
                $this->columnRowFromRowData($rowData, $column, $row);
            }
        }

        
        fclose($fileHandle);

        
        return $spreadsheet;
    }

    
    private function columnRowFromRowData(array $rowData, string &$column, string &$row): void
    {
        foreach ($rowData as $rowDatum) {
            $char0 = $rowDatum[0];
            if ($char0 === 'X' || $char0 == 'C') {
                $column = substr($rowDatum, 1);
            } elseif ($char0 === 'Y' || $char0 == 'R') {
                $row = substr($rowDatum, 1);
            }
        }
    }

    
    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    
    public function setSheetIndex(int $sheetIndex): static
    {
        $this->sheetIndex = $sheetIndex;

        return $this;
    }
}
