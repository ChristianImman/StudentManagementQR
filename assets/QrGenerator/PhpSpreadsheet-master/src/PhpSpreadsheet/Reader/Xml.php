<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use DateTime;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Cell\AddressHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\DefinedName;
use PhpOffice\PhpSpreadsheet\Helper\Html as HelperHtml;
use PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Reader\Xml\PageSettings;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Properties;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Style;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;
use Throwable;


class Xml extends BaseReader
{
    public const NAMESPACES_SS = 'urn:schemas-microsoft-com:office:spreadsheet';

    
    protected array $styles = [];

    
    public function __construct()
    {
        parent::__construct();
        $this->securityScanner = XmlScanner::getInstance($this);
        
        $unentity = [self::class, 'unentity'];
        $this->securityScanner->setAdditionalCallback($unentity);
    }

    public static function unentity(string $contents): string
    {
        $contents = preg_replace('/&(amp|lt|gt|quot|apos);/', "\u{fffe}\u{feff}\$1;", trim($contents)) ?? $contents;
        $contents = html_entity_decode($contents, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
        $contents = str_replace("\u{fffe}\u{feff}", '&', $contents);

        return $contents;
    }

    private string $fileContents = '';

    private string $xmlFailMessage = '';

    
    public static function xmlMappings(): array
    {
        return array_merge(
            Style\Fill::FILL_MAPPINGS,
            Style\Border::BORDER_MAPPINGS
        );
    }

    
    public function canRead(string $filename): bool
    {
        
        
        
        
        
        
        
        
        

        $signature = [
            '<?xml version="1.0"',
            'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet',
        ];

        
        $data = (string) file_get_contents($filename);
        $data = $this->getSecurityScannerOrThrow()->scan($data);

        
        

        $valid = true;
        foreach ($signature as $match) {
            
            if (!str_contains($data, $match)) {
                $valid = false;

                break;
            }
        }

        $this->fileContents = $data;

        return $valid;
    }

    
    private function trySimpleXMLLoadStringPrivate(string $filename, string $fileOrString = 'file'): SimpleXMLElement|bool
    {
        $this->xmlFailMessage = "Cannot load invalid XML $fileOrString: " . $filename;
        $xml = false;

        try {
            $data = $this->fileContents;
            $continue = true;
            if ($data === '' && $fileOrString === 'file') {
                if ($filename === '') {
                    $this->xmlFailMessage = 'Cannot load empty path';
                    $continue = false;
                } else {
                    $datax = @file_get_contents($filename);
                    $data = $datax ?: '';
                    $continue = $datax !== false;
                }
            }
            if ($continue) {
                $xml = @simplexml_load_string(
                    $this->getSecurityScannerOrThrow()
                        ->scan($data)
                );
            }
        } catch (Throwable $e) {
            throw new Exception($this->xmlFailMessage, 0, $e);
        }
        $this->fileContents = '';

        return $xml;
    }

    
    public function listWorksheetNames(string $filename): array
    {
        File::assertFile($filename);
        if (!$this->canRead($filename)) {
            throw new Exception($filename . ' is an Invalid Spreadsheet file.');
        }

        $worksheetNames = [];

        $xml = $this->trySimpleXMLLoadStringPrivate($filename);
        if ($xml === false) {
            throw new Exception("Problem reading {$filename}");
        }

        $xml_ss = $xml->children(self::NAMESPACES_SS);
        foreach ($xml_ss->Worksheet as $worksheet) {
            $worksheet_ss = self::getAttributes($worksheet, self::NAMESPACES_SS);
            $worksheetNames[] = (string) $worksheet_ss['Name'];
        }

        return $worksheetNames;
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        File::assertFile($filename);
        if (!$this->canRead($filename)) {
            throw new Exception($filename . ' is an Invalid Spreadsheet file.');
        }

        $worksheetInfo = [];

        $xml = $this->trySimpleXMLLoadStringPrivate($filename);
        if ($xml === false) {
            throw new Exception("Problem reading {$filename}");
        }

        $worksheetID = 1;
        $xml_ss = $xml->children(self::NAMESPACES_SS);
        foreach ($xml_ss->Worksheet as $worksheet) {
            $worksheet_ss = self::getAttributes($worksheet, self::NAMESPACES_SS);

            $tmpInfo = [];
            $tmpInfo['worksheetName'] = '';
            $tmpInfo['lastColumnLetter'] = 'A';
            $tmpInfo['lastColumnIndex'] = 0;
            $tmpInfo['totalRows'] = 0;
            $tmpInfo['totalColumns'] = 0;

            $tmpInfo['worksheetName'] = "Worksheet_{$worksheetID}";
            if (isset($worksheet_ss['Name'])) {
                $tmpInfo['worksheetName'] = (string) $worksheet_ss['Name'];
            }

            if (isset($worksheet->Table->Row)) {
                $rowIndex = 0;

                foreach ($worksheet->Table->Row as $rowData) {
                    $columnIndex = 0;
                    $rowHasData = false;

                    foreach ($rowData->Cell as $cell) {
                        if (isset($cell->Data)) {
                            $tmpInfo['lastColumnIndex'] = max($tmpInfo['lastColumnIndex'], $columnIndex);
                            $rowHasData = true;
                        }

                        ++$columnIndex;
                    }

                    ++$rowIndex;

                    if ($rowHasData) {
                        $tmpInfo['totalRows'] = max($tmpInfo['totalRows'], $rowIndex);
                    }
                }
            }

            $tmpInfo['lastColumnLetter'] = Coordinate::stringFromColumnIndex($tmpInfo['lastColumnIndex'] + 1);
            $tmpInfo['totalColumns'] = $tmpInfo['lastColumnIndex'] + 1;
            $tmpInfo['sheetState'] = Worksheet::SHEETSTATE_VISIBLE;

            $worksheetInfo[] = $tmpInfo;
            ++$worksheetID;
        }

        return $worksheetInfo;
    }

    
    public function loadSpreadsheetFromString(string $contents): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);
        $spreadsheet->removeSheetByIndex(0);

        
        return $this->loadIntoExisting($contents, $spreadsheet, true);
    }

    
    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);
        $spreadsheet->removeSheetByIndex(0);

        
        return $this->loadIntoExisting($filename, $spreadsheet);
    }

    
    public function loadIntoExisting(string $filename, Spreadsheet $spreadsheet, bool $useContents = false): Spreadsheet
    {
        if ($useContents) {
            $this->fileContents = $filename;
            $fileOrString = 'string';
        } else {
            File::assertFile($filename);
            if (!$this->canRead($filename)) {
                throw new Exception($filename . ' is an Invalid Spreadsheet file.');
            }
            $fileOrString = 'file';
        }

        $xml = $this->trySimpleXMLLoadStringPrivate($filename, $fileOrString);
        if ($xml === false) {
            throw new Exception($this->xmlFailMessage);
        }

        $namespaces = $xml->getNamespaces(true);

        (new Properties($spreadsheet))->readProperties($xml, $namespaces);

        $this->styles = (new Style())->parseStyles($xml, $namespaces);
        if (isset($this->styles['Default']) && is_array($this->styles['Default'])) {
            $spreadsheet->getCellXfCollection()[0]->applyFromArray($this->styles['Default']);
        }

        $worksheetID = 0;
        $xml_ss = $xml->children(self::NAMESPACES_SS);

        
        foreach ($xml_ss->Worksheet as $worksheetx) {
            $worksheet = $worksheetx ?? new SimpleXMLElement('<xml></xml>');
            $worksheet_ss = self::getAttributes($worksheet, self::NAMESPACES_SS);

            if (
                isset($this->loadSheetsOnly, $worksheet_ss['Name'])
                && (!in_array($worksheet_ss['Name'], $this->loadSheetsOnly))
            ) {
                continue;
            }

            
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($worksheetID);
            $worksheetName = '';
            if (isset($worksheet_ss['Name'])) {
                $worksheetName = (string) $worksheet_ss['Name'];
                
                
                
                $spreadsheet->getActiveSheet()->setTitle($worksheetName, false, false);
            }
            if (isset($worksheet_ss['Protected'])) {
                $protection = (string) $worksheet_ss['Protected'] === '1';
                $spreadsheet->getActiveSheet()->getProtection()->setSheet($protection);
            }

            
            if (isset($worksheet->Names[0])) {
                foreach ($worksheet->Names[0] as $definedName) {
                    $definedName_ss = self::getAttributes($definedName, self::NAMESPACES_SS);
                    $name = (string) $definedName_ss['Name'];
                    $definedValue = (string) $definedName_ss['RefersTo'];
                    $convertedValue = AddressHelper::convertFormulaToA1($definedValue);
                    if ($convertedValue[0] === '=') {
                        $convertedValue = substr($convertedValue, 1);
                    }
                    $spreadsheet->addDefinedName(DefinedName::createInstance($name, $spreadsheet->getActiveSheet(), $convertedValue, true));
                }
            }

            $columnID = 'A';
            if (isset($worksheet->Table->Column)) {
                foreach ($worksheet->Table->Column as $columnData) {
                    $columnData_ss = self::getAttributes($columnData, self::NAMESPACES_SS);
                    $colspan = 0;
                    if (isset($columnData_ss['Span'])) {
                        $spanAttr = (string) $columnData_ss['Span'];
                        if (is_numeric($spanAttr)) {
                            $colspan = max(0, (int) $spanAttr);
                        }
                    }
                    if (isset($columnData_ss['Index'])) {
                        $columnID = Coordinate::stringFromColumnIndex((int) $columnData_ss['Index']);
                    }
                    $columnWidth = null;
                    if (isset($columnData_ss['Width'])) {
                        $columnWidth = $columnData_ss['Width'];
                    }
                    $columnVisible = null;
                    if (isset($columnData_ss['Hidden'])) {
                        $columnVisible = ((string) $columnData_ss['Hidden']) !== '1';
                    }
                    while ($colspan >= 0) {
                        
                        if (isset($columnWidth)) {
                            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setWidth($columnWidth / 5.4);
                        }
                        if (isset($columnVisible)) {
                            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setVisible($columnVisible);
                        }
                        ++$columnID;
                        --$colspan;
                    }
                }
            }

            $rowID = 1;
            if (isset($worksheet->Table->Row)) {
                $additionalMergedCells = 0;
                foreach ($worksheet->Table->Row as $rowData) {
                    $rowHasData = false;
                    $row_ss = self::getAttributes($rowData, self::NAMESPACES_SS);
                    if (isset($row_ss['Index'])) {
                        $rowID = (int) $row_ss['Index'];
                    }
                    if (isset($row_ss['Hidden'])) {
                        $rowVisible = ((string) $row_ss['Hidden']) !== '1';
                        $spreadsheet->getActiveSheet()->getRowDimension($rowID)->setVisible($rowVisible);
                    }

                    $columnID = 'A';
                    foreach ($rowData->Cell as $cell) {
                        $arrayRef = '';
                        $cell_ss = self::getAttributes($cell, self::NAMESPACES_SS);
                        if (isset($cell_ss['Index'])) {
                            $columnID = Coordinate::stringFromColumnIndex((int) $cell_ss['Index']);
                        }
                        $cellRange = $columnID . $rowID;
                        if (isset($cell_ss['ArrayRange'])) {
                            $arrayRange = (string) $cell_ss['ArrayRange'];
                            $arrayRef = AddressHelper::convertFormulaToA1($arrayRange, $rowID, Coordinate::columnIndexFromString($columnID));
                        }

                        if (!$this->getReadFilter()->readCell($columnID, $rowID, $worksheetName)) {
                            ++$columnID;

                            continue;
                        }

                        if (isset($cell_ss['HRef'])) {
                            $spreadsheet->getActiveSheet()->getCell($cellRange)->getHyperlink()->setUrl((string) $cell_ss['HRef']);
                        }

                        if ((isset($cell_ss['MergeAcross'])) || (isset($cell_ss['MergeDown']))) {
                            $columnTo = $columnID;
                            if (isset($cell_ss['MergeAcross'])) {
                                $additionalMergedCells += (int) $cell_ss['MergeAcross'];
                                $columnTo = Coordinate::stringFromColumnIndex((int) (Coordinate::columnIndexFromString($columnID) + $cell_ss['MergeAcross']));
                            }
                            $rowTo = $rowID;
                            if (isset($cell_ss['MergeDown'])) {
                                $rowTo = $rowTo + $cell_ss['MergeDown'];
                            }
                            $cellRange .= ':' . $columnTo . $rowTo;
                            $spreadsheet->getActiveSheet()->mergeCells($cellRange, Worksheet::MERGE_CELL_CONTENT_HIDE);
                        }

                        $hasCalculatedValue = false;
                        $cellDataFormula = '';
                        if (isset($cell_ss['Formula'])) {
                            $cellDataFormula = $cell_ss['Formula'];
                            $hasCalculatedValue = true;
                            if ($arrayRef !== '') {
                                $spreadsheet->getActiveSheet()->getCell($columnID . $rowID)->setFormulaAttributes(['t' => 'array', 'ref' => $arrayRef]);
                            }
                        }
                        if (isset($cell->Data)) {
                            $cellData = $cell->Data;
                            $cellValue = (string) $cellData;
                            $type = DataType::TYPE_NULL;
                            $cellData_ss = self::getAttributes($cellData, self::NAMESPACES_SS);
                            if (isset($cellData_ss['Type'])) {
                                $cellDataType = $cellData_ss['Type'];
                                switch ($cellDataType) {
                                    
                                    case 'String':
                                        $type = DataType::TYPE_STRING;
                                        $rich = $cellData->children('http://www.w3.org/TR/REC-html40');
                                        if ($rich) {
                                            
                                            
                                            $content = $cellData->asXML() ?: '';
                                            $html = new HelperHtml();
                                            $cellValue = $html->toRichTextObject($content, true);
                                        }

                                        break;
                                    case 'Number':
                                        $type = DataType::TYPE_NUMERIC;
                                        $cellValue = (float) $cellValue;
                                        if (floor($cellValue) == $cellValue) {
                                            $cellValue = (int) $cellValue;
                                        }

                                        break;
                                    case 'Boolean':
                                        $type = DataType::TYPE_BOOL;
                                        $cellValue = ($cellValue != 0);

                                        break;
                                    case 'DateTime':
                                        $type = DataType::TYPE_NUMERIC;
                                        $dateTime = new DateTime($cellValue, new DateTimeZone('UTC'));
                                        $cellValue = Date::PHPToExcel($dateTime);

                                        break;
                                    case 'Error':
                                        $type = DataType::TYPE_ERROR;
                                        $hasCalculatedValue = false;

                                        break;
                                }
                            }

                            $originalType = $type;
                            if ($hasCalculatedValue) {
                                $type = DataType::TYPE_FORMULA;
                                $columnNumber = Coordinate::columnIndexFromString($columnID);
                                $cellDataFormula = AddressHelper::convertFormulaToA1($cellDataFormula, $rowID, $columnNumber);
                            }

                            $spreadsheet->getActiveSheet()->getCell($columnID . $rowID)->setValueExplicit((($hasCalculatedValue) ? $cellDataFormula : $cellValue), $type);
                            if ($hasCalculatedValue) {
                                $spreadsheet->getActiveSheet()->getCell($columnID . $rowID)->setCalculatedValue($cellValue, $originalType === DataType::TYPE_NUMERIC);
                            }
                            $rowHasData = true;
                        }

                        if (isset($cell->Comment)) {
                            $this->parseCellComment($cell->Comment, $spreadsheet, $columnID, $rowID);
                        }

                        if (isset($cell_ss['StyleID'])) {
                            $style = (string) $cell_ss['StyleID'];
                            if ((isset($this->styles[$style])) && is_array($this->styles[$style]) && (!empty($this->styles[$style]))) {
                                $spreadsheet->getActiveSheet()->getStyle($cellRange)
                                    ->applyFromArray($this->styles[$style]);
                            }
                        }
                        
                        ++$columnID;
                        while ($additionalMergedCells > 0) {
                            ++$columnID;
                            --$additionalMergedCells;
                        }
                    }

                    if ($rowHasData) {
                        if (isset($row_ss['Height'])) {
                            $rowHeight = $row_ss['Height'];
                            $spreadsheet->getActiveSheet()->getRowDimension($rowID)->setRowHeight((float) $rowHeight);
                        }
                    }

                    ++$rowID;
                }
            }

            $dataValidations = new Xml\DataValidations();
            $dataValidations->loadDataValidations($worksheet, $spreadsheet);
            $xmlX = $worksheet->children(Namespaces::URN_EXCEL);
            if (isset($xmlX->WorksheetOptions)) {
                if (isset($xmlX->WorksheetOptions->ShowPageBreakZoom)) {
                    $spreadsheet->getActiveSheet()->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
                }
                if (isset($xmlX->WorksheetOptions->Zoom)) {
                    $zoomScaleNormal = (int) $xmlX->WorksheetOptions->Zoom;
                    if ($zoomScaleNormal > 0) {
                        $spreadsheet->getActiveSheet()->getSheetView()->setZoomScaleNormal($zoomScaleNormal);
                        $spreadsheet->getActiveSheet()->getSheetView()->setZoomScale($zoomScaleNormal);
                    }
                }
                if (isset($xmlX->WorksheetOptions->PageBreakZoom)) {
                    $zoomScaleNormal = (int) $xmlX->WorksheetOptions->PageBreakZoom;
                    if ($zoomScaleNormal > 0) {
                        $spreadsheet->getActiveSheet()->getSheetView()->setZoomScaleSheetLayoutView($zoomScaleNormal);
                    }
                }
                if (isset($xmlX->WorksheetOptions->ShowPageBreakZoom)) {
                    $spreadsheet->getActiveSheet()->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
                }
                if (isset($xmlX->WorksheetOptions->FreezePanes)) {
                    $freezeRow = $freezeColumn = 1;
                    if (isset($xmlX->WorksheetOptions->SplitHorizontal)) {
                        $freezeRow = (int) $xmlX->WorksheetOptions->SplitHorizontal + 1;
                    }
                    if (isset($xmlX->WorksheetOptions->SplitVertical)) {
                        $freezeColumn = (int) $xmlX->WorksheetOptions->SplitVertical + 1;
                    }
                    $leftTopRow = (string) $xmlX->WorksheetOptions->TopRowBottomPane;
                    $leftTopColumn = (string) $xmlX->WorksheetOptions->LeftColumnRightPane;
                    if (is_numeric($leftTopRow) && is_numeric($leftTopColumn)) {
                        $leftTopCoordinate = Coordinate::stringFromColumnIndex((int) $leftTopColumn + 1) . (string) ($leftTopRow + 1);
                        $spreadsheet->getActiveSheet()->freezePane(Coordinate::stringFromColumnIndex($freezeColumn) . (string) $freezeRow, $leftTopCoordinate, !isset($xmlX->WorksheetOptions->FrozenNoSplit));
                    } else {
                        $spreadsheet->getActiveSheet()->freezePane(Coordinate::stringFromColumnIndex($freezeColumn) . (string) $freezeRow, null, !isset($xmlX->WorksheetOptions->FrozenNoSplit));
                    }
                } elseif (isset($xmlX->WorksheetOptions->SplitVertical) || isset($xmlX->WorksheetOptions->SplitHorizontal)) {
                    if (isset($xmlX->WorksheetOptions->SplitHorizontal)) {
                        $ySplit = (int) $xmlX->WorksheetOptions->SplitHorizontal;
                        $spreadsheet->getActiveSheet()->setYSplit($ySplit);
                    }
                    if (isset($xmlX->WorksheetOptions->SplitVertical)) {
                        $xSplit = (int) $xmlX->WorksheetOptions->SplitVertical;
                        $spreadsheet->getActiveSheet()->setXSplit($xSplit);
                    }
                    if (isset($xmlX->WorksheetOptions->LeftColumnVisible) || isset($xmlX->WorksheetOptions->TopRowVisible)) {
                        $leftTopColumn = $leftTopRow = 1;
                        if (isset($xmlX->WorksheetOptions->LeftColumnVisible)) {
                            $leftTopColumn = 1 + (int) $xmlX->WorksheetOptions->LeftColumnVisible;
                        }
                        if (isset($xmlX->WorksheetOptions->TopRowVisible)) {
                            $leftTopRow = 1 + (int) $xmlX->WorksheetOptions->TopRowVisible;
                        }
                        $leftTopCoordinate = Coordinate::stringFromColumnIndex($leftTopColumn) . "$leftTopRow";
                        $spreadsheet->getActiveSheet()->setTopLeftCell($leftTopCoordinate);
                    }

                    $leftTopColumn = $leftTopRow = 1;
                    if (isset($xmlX->WorksheetOptions->LeftColumnRightPane)) {
                        $leftTopColumn = 1 + (int) $xmlX->WorksheetOptions->LeftColumnRightPane;
                    }
                    if (isset($xmlX->WorksheetOptions->TopRowBottomPane)) {
                        $leftTopRow = 1 + (int) $xmlX->WorksheetOptions->TopRowBottomPane;
                    }
                    $leftTopCoordinate = Coordinate::stringFromColumnIndex($leftTopColumn) . "$leftTopRow";
                    $spreadsheet->getActiveSheet()->setPaneTopLeftCell($leftTopCoordinate);
                }
                (new PageSettings($xmlX))->loadPageSettings($spreadsheet);
                if (isset($xmlX->WorksheetOptions->TopRowVisible, $xmlX->WorksheetOptions->LeftColumnVisible)) {
                    $leftTopRow = (string) $xmlX->WorksheetOptions->TopRowVisible;
                    $leftTopColumn = (string) $xmlX->WorksheetOptions->LeftColumnVisible;
                    if (is_numeric($leftTopRow) && is_numeric($leftTopColumn)) {
                        $leftTopCoordinate = Coordinate::stringFromColumnIndex((int) $leftTopColumn + 1) . (string) ($leftTopRow + 1);
                        $spreadsheet->getActiveSheet()->setTopLeftCell($leftTopCoordinate);
                    }
                }
                $rangeCalculated = false;
                if (isset($xmlX->WorksheetOptions->Panes->Pane->RangeSelection)) {
                    if (1 === preg_match('/^R(\d+)C(\d+):R(\d+)C(\d+)$/', (string) $xmlX->WorksheetOptions->Panes->Pane->RangeSelection, $selectionMatches)) {
                        $selectedCell = Coordinate::stringFromColumnIndex((int) $selectionMatches[2])
                            . $selectionMatches[1]
                            . ':'
                            . Coordinate::stringFromColumnIndex((int) $selectionMatches[4])
                            . $selectionMatches[3];
                        $spreadsheet->getActiveSheet()->setSelectedCells($selectedCell);
                        $rangeCalculated = true;
                    }
                }
                if (!$rangeCalculated) {
                    if (isset($xmlX->WorksheetOptions->Panes->Pane->ActiveRow)) {
                        $activeRow = (string) $xmlX->WorksheetOptions->Panes->Pane->ActiveRow;
                    } else {
                        $activeRow = 0;
                    }
                    if (isset($xmlX->WorksheetOptions->Panes->Pane->ActiveCol)) {
                        $activeColumn = (string) $xmlX->WorksheetOptions->Panes->Pane->ActiveCol;
                    } else {
                        $activeColumn = 0;
                    }
                    if (is_numeric($activeRow) && is_numeric($activeColumn)) {
                        $selectedCell = Coordinate::stringFromColumnIndex((int) $activeColumn + 1) . (string) ($activeRow + 1);
                        $spreadsheet->getActiveSheet()->setSelectedCells($selectedCell);
                    }
                }
            }
            if (isset($xmlX->PageBreaks)) {
                if (isset($xmlX->PageBreaks->ColBreaks)) {
                    foreach ($xmlX->PageBreaks->ColBreaks->ColBreak as $colBreak) {
                        $colBreak = (string) $colBreak->Column;
                        $spreadsheet->getActiveSheet()->setBreak([1 + (int) $colBreak, 1], Worksheet::BREAK_COLUMN);
                    }
                }
                if (isset($xmlX->PageBreaks->RowBreaks)) {
                    foreach ($xmlX->PageBreaks->RowBreaks->RowBreak as $rowBreak) {
                        $rowBreak = (string) $rowBreak->Row;
                        $spreadsheet->getActiveSheet()->setBreak([1, (int) $rowBreak], Worksheet::BREAK_ROW);
                    }
                }
            }
            ++$worksheetID;
        }

        
        $activeSheetIndex = 0;
        if (isset($xml->ExcelWorkbook->ActiveSheet)) {
            $activeSheetIndex = (int) (string) $xml->ExcelWorkbook->ActiveSheet;
        }
        $activeWorksheet = $spreadsheet->setActiveSheetIndex($activeSheetIndex);
        if (isset($xml->Names[0])) {
            foreach ($xml->Names[0] as $definedName) {
                $definedName_ss = self::getAttributes($definedName, self::NAMESPACES_SS);
                $name = (string) $definedName_ss['Name'];
                $definedValue = (string) $definedName_ss['RefersTo'];
                $convertedValue = AddressHelper::convertFormulaToA1($definedValue);
                if ($convertedValue[0] === '=') {
                    $convertedValue = substr($convertedValue, 1);
                }
                $spreadsheet->addDefinedName(DefinedName::createInstance($name, $activeWorksheet, $convertedValue));
            }
        }

        
        return $spreadsheet;
    }

    protected function parseCellComment(
        SimpleXMLElement $comment,
        Spreadsheet $spreadsheet,
        string $columnID,
        int $rowID
    ): void {
        $commentAttributes = $comment->attributes(self::NAMESPACES_SS);
        $author = 'unknown';
        if (isset($commentAttributes->Author)) {
            $author = (string) $commentAttributes->Author;
        }

        $node = $comment->Data->asXML();
        $annotation = strip_tags((string) $node);
        $spreadsheet->getActiveSheet()->getComment($columnID . $rowID)
            ->setAuthor($author)
            ->setText($this->parseRichText($annotation));
    }

    protected function parseRichText(string $annotation): RichText
    {
        $value = new RichText();

        $value->createText($annotation);

        return $value;
    }

    private static function getAttributes(?SimpleXMLElement $simple, string $node): SimpleXMLElement
    {
        return ($simple === null)
            ? new SimpleXMLElement('<xml></xml>')
            : ($simple->attributes($node) ?? new SimpleXMLElement('<xml></xml>'));
    }
}
