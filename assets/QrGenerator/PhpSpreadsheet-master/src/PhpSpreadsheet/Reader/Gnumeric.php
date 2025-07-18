<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\DefinedName;
use PhpOffice\PhpSpreadsheet\Reader\Gnumeric\PageSetup;
use PhpOffice\PhpSpreadsheet\Reader\Gnumeric\Properties;
use PhpOffice\PhpSpreadsheet\Reader\Gnumeric\Styles;
use PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner;
use PhpOffice\PhpSpreadsheet\ReferenceHelper;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;
use XMLReader;

class Gnumeric extends BaseReader
{
    const NAMESPACE_GNM = 'http://www.gnumeric.org/v10.dtd'; 

    const NAMESPACE_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    const NAMESPACE_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';

    const NAMESPACE_XLINK = 'http://www.w3.org/1999/xlink';

    const NAMESPACE_DC = 'http://purl.org/dc/elements/1.1/';

    const NAMESPACE_META = 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0';

    const NAMESPACE_OOO = 'http://openoffice.org/2004/office';

    const GNM_SHEET_VISIBILITY_VISIBLE = 'GNM_SHEET_VISIBILITY_VISIBLE';
    const GNM_SHEET_VISIBILITY_HIDDEN = 'GNM_SHEET_VISIBILITY_HIDDEN';

    
    private array $expressions = [];

    
    private Spreadsheet $spreadsheet;

    private ReferenceHelper $referenceHelper;

    
    public static array $mappings = [
        'dataType' => [
            '10' => DataType::TYPE_NULL,
            '20' => DataType::TYPE_BOOL,
            '30' => DataType::TYPE_NUMERIC, 
            '40' => DataType::TYPE_NUMERIC, 
            '50' => DataType::TYPE_ERROR,
            '60' => DataType::TYPE_STRING,
            
            
        ],
    ];

    
    public function __construct()
    {
        parent::__construct();
        $this->referenceHelper = ReferenceHelper::getInstance();
        $this->securityScanner = XmlScanner::getInstance($this);
    }

    
    public function canRead(string $filename): bool
    {
        $data = null;
        if (File::testFileNoThrow($filename)) {
            $data = $this->gzfileGetContents($filename);
            if (!str_contains($data, self::NAMESPACE_GNM)) {
                $data = '';
            }
        }

        return !empty($data);
    }

    private static function matchXml(XMLReader $xml, string $expectedLocalName): bool
    {
        return $xml->namespaceURI === self::NAMESPACE_GNM
            && $xml->localName === $expectedLocalName
            && $xml->nodeType === XMLReader::ELEMENT;
    }

    
    public function listWorksheetNames(string $filename): array
    {
        File::assertFile($filename);
        if (!$this->canRead($filename)) {
            throw new Exception($filename . ' is an invalid Gnumeric file.');
        }

        $xml = new XMLReader();
        $contents = $this->gzfileGetContents($filename);
        $xml->xml($contents);
        $xml->setParserProperty(2, true);

        $worksheetNames = [];
        while ($xml->read()) {
            if (self::matchXml($xml, 'SheetName')) {
                $xml->read(); 
                $worksheetNames[] = (string) $xml->value;
            } elseif (self::matchXml($xml, 'Sheets')) {
                
                break;
            }
        }

        return $worksheetNames;
    }

    
    public function listWorksheetInfo(string $filename): array
    {
        File::assertFile($filename);
        if (!$this->canRead($filename)) {
            throw new Exception($filename . ' is an invalid Gnumeric file.');
        }

        $xml = new XMLReader();
        $contents = $this->gzfileGetContents($filename);
        $xml->xml($contents);
        $xml->setParserProperty(2, true);

        $worksheetInfo = [];
        while ($xml->read()) {
            if (self::matchXml($xml, 'Sheet')) {
                $tmpInfo = [
                    'worksheetName' => '',
                    'lastColumnLetter' => 'A',
                    'lastColumnIndex' => 0,
                    'totalRows' => 0,
                    'totalColumns' => 0,
                    'sheetState' => Worksheet::SHEETSTATE_VISIBLE,
                ];
                $visibility = $xml->getAttribute('Visibility');
                if ((string) $visibility === self::GNM_SHEET_VISIBILITY_HIDDEN) {
                    $tmpInfo['sheetState'] = Worksheet::SHEETSTATE_HIDDEN;
                }

                while ($xml->read()) {
                    if (self::matchXml($xml, 'Name')) {
                        $xml->read(); 
                        $tmpInfo['worksheetName'] = (string) $xml->value;
                    } elseif (self::matchXml($xml, 'MaxCol')) {
                        $xml->read(); 
                        $tmpInfo['lastColumnIndex'] = (int) $xml->value;
                        $tmpInfo['totalColumns'] = (int) $xml->value + 1;
                    } elseif (self::matchXml($xml, 'MaxRow')) {
                        $xml->read(); 
                        $tmpInfo['totalRows'] = (int) $xml->value + 1;

                        break;
                    }
                }
                $tmpInfo['lastColumnLetter'] = Coordinate::stringFromColumnIndex($tmpInfo['lastColumnIndex'] + 1);
                $worksheetInfo[] = $tmpInfo;
            }
        }

        return $worksheetInfo;
    }

    private function gzfileGetContents(string $filename): string
    {
        $data = '';
        $contents = @file_get_contents($filename);
        if ($contents !== false) {
            if (str_starts_with($contents, "\x1f\x8b")) {
                
                if (function_exists('gzdecode')) {
                    $contents = @gzdecode($contents);
                    if ($contents !== false) {
                        $data = $contents;
                    }
                }
            } else {
                $data = $contents;
            }
        }
        if ($data !== '') {
            $data = $this->getSecurityScannerOrThrow()->scan($data);
        }

        return $data;
    }

    
    public static function gnumericMappings(): array
    {
        return array_merge(self::$mappings, Styles::$mappings);
    }

    private function processComments(SimpleXMLElement $sheet): void
    {
        if ((!$this->readDataOnly) && (isset($sheet->Objects))) {
            foreach ($sheet->Objects->children(self::NAMESPACE_GNM) as $key => $comment) {
                $commentAttributes = $comment->attributes();
                
                if ($commentAttributes && $commentAttributes->Text) {
                    $this->spreadsheet->getActiveSheet()->getComment((string) $commentAttributes->ObjectBound)
                        ->setAuthor((string) $commentAttributes->Author)
                        ->setText($this->parseRichText((string) $commentAttributes->Text));
                }
            }
        }
    }

    private static function testSimpleXml(mixed $value): SimpleXMLElement
    {
        return ($value instanceof SimpleXMLElement) ? $value : new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
    }

    
    protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
        $spreadsheet = $this->newSpreadsheet();
        $spreadsheet->setValueBinder($this->valueBinder);
        $spreadsheet->removeSheetByIndex(0);

        
        return $this->loadIntoExisting($filename, $spreadsheet);
    }

    
    public function loadIntoExisting(string $filename, Spreadsheet $spreadsheet): Spreadsheet
    {
        $this->spreadsheet = $spreadsheet;
        File::assertFile($filename);
        if (!$this->canRead($filename)) {
            throw new Exception($filename . ' is an invalid Gnumeric file.');
        }

        $gFileData = $this->gzfileGetContents($filename);

        
        $securityScanner = $this->securityScanner;
        $xml2 = simplexml_load_string($securityScanner->scan($gFileData));
        $xml = self::testSimpleXml($xml2);

        $gnmXML = $xml->children(self::NAMESPACE_GNM);
        (new Properties($this->spreadsheet))->readProperties($xml, $gnmXML);

        $worksheetID = 0;
        foreach ($gnmXML->Sheets->Sheet as $sheetOrNull) {
            $sheet = self::testSimpleXml($sheetOrNull);
            $worksheetName = (string) $sheet->Name;
            if (is_array($this->loadSheetsOnly) && !in_array($worksheetName, $this->loadSheetsOnly, true)) {
                continue;
            }

            $maxRow = $maxCol = 0;

            
            $this->spreadsheet->createSheet();
            $this->spreadsheet->setActiveSheetIndex($worksheetID);
            
            
            
            $this->spreadsheet->getActiveSheet()->setTitle($worksheetName, false, false);

            $visibility = $sheet->attributes()['Visibility'] ?? self::GNM_SHEET_VISIBILITY_VISIBLE;
            if ((string) $visibility !== self::GNM_SHEET_VISIBILITY_VISIBLE) {
                $this->spreadsheet->getActiveSheet()->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            }

            if (!$this->readDataOnly) {
                (new PageSetup($this->spreadsheet))
                    ->printInformation($sheet)
                    ->sheetMargins($sheet);
            }

            foreach ($sheet->Cells->Cell as $cellOrNull) {
                $cell = self::testSimpleXml($cellOrNull);
                $cellAttributes = self::testSimpleXml($cell->attributes());
                $row = (int) $cellAttributes->Row + 1;
                $column = (int) $cellAttributes->Col;

                $maxRow = max($maxRow, $row);
                $maxCol = max($maxCol, $column);

                $column = Coordinate::stringFromColumnIndex($column + 1);

                
                if (!$this->getReadFilter()->readCell($column, $row, $worksheetName)) {
                    continue;
                }

                $this->loadCell($cell, $worksheetName, $cellAttributes, $column, $row);
            }

            if ($sheet->Styles !== null) {
                (new Styles($this->spreadsheet, $this->readDataOnly))->read($sheet, $maxRow, $maxCol);
            }

            $this->processComments($sheet);
            $this->processColumnWidths($sheet, $maxCol);
            $this->processRowHeights($sheet, $maxRow);
            $this->processMergedCells($sheet);
            $this->processAutofilter($sheet);

            $this->setSelectedCells($sheet);
            ++$worksheetID;
        }

        $this->processDefinedNames($gnmXML);

        $this->setSelectedSheet($gnmXML);

        
        return $this->spreadsheet;
    }

    private function setSelectedSheet(SimpleXMLElement $gnmXML): void
    {
        if (isset($gnmXML->UIData)) {
            $attributes = self::testSimpleXml($gnmXML->UIData->attributes());
            $selectedSheet = (int) $attributes['SelectedTab'];
            $this->spreadsheet->setActiveSheetIndex($selectedSheet);
        }
    }

    private function setSelectedCells(?SimpleXMLElement $sheet): void
    {
        if ($sheet !== null && isset($sheet->Selections)) {
            foreach ($sheet->Selections as $selection) {
                $startCol = (int) ($selection->StartCol ?? 0);
                $startRow = (int) ($selection->StartRow ?? 0) + 1;
                $endCol = (int) ($selection->EndCol ?? $startCol);
                $endRow = (int) ($selection->endRow ?? 0) + 1;

                $startColumn = Coordinate::stringFromColumnIndex($startCol + 1);
                $endColumn = Coordinate::stringFromColumnIndex($endCol + 1);

                $startCell = "{$startColumn}{$startRow}";
                $endCell = "{$endColumn}{$endRow}";
                $selectedRange = $startCell . (($endCell !== $startCell) ? ':' . $endCell : '');
                $this->spreadsheet->getActiveSheet()->setSelectedCell($selectedRange);

                break;
            }
        }
    }

    private function processMergedCells(?SimpleXMLElement $sheet): void
    {
        
        if ($sheet !== null && isset($sheet->MergedRegions)) {
            foreach ($sheet->MergedRegions->Merge as $mergeCells) {
                if (str_contains((string) $mergeCells, ':')) {
                    $this->spreadsheet->getActiveSheet()->mergeCells($mergeCells, Worksheet::MERGE_CELL_CONTENT_HIDE);
                }
            }
        }
    }

    private function processAutofilter(?SimpleXMLElement $sheet): void
    {
        if ($sheet !== null && isset($sheet->Filters)) {
            foreach ($sheet->Filters->Filter as $autofilter) {
                $attributes = $autofilter->attributes();
                if (isset($attributes['Area'])) {
                    $this->spreadsheet->getActiveSheet()->setAutoFilter((string) $attributes['Area']);
                }
            }
        }
    }

    private function setColumnWidth(int $whichColumn, float $defaultWidth): void
    {
        $this->spreadsheet->getActiveSheet()
            ->getColumnDimension(
                Coordinate::stringFromColumnIndex($whichColumn + 1)
            )
            ->setWidth($defaultWidth);
    }

    private function setColumnInvisible(int $whichColumn): void
    {
        $this->spreadsheet->getActiveSheet()
            ->getColumnDimension(
                Coordinate::stringFromColumnIndex($whichColumn + 1)
            )
            ->setVisible(false);
    }

    private function processColumnLoop(int $whichColumn, int $maxCol, ?SimpleXMLElement $columnOverride, float $defaultWidth): int
    {
        $columnOverride = self::testSimpleXml($columnOverride);
        $columnAttributes = self::testSimpleXml($columnOverride->attributes());
        $column = $columnAttributes['No'];
        $columnWidth = ((float) $columnAttributes['Unit']) / 5.4;
        $hidden = (isset($columnAttributes['Hidden'])) && ((string) $columnAttributes['Hidden'] == '1');
        $columnCount = (int) ($columnAttributes['Count'] ?? 1);
        while ($whichColumn < $column) {
            $this->setColumnWidth($whichColumn, $defaultWidth);
            ++$whichColumn;
        }
        while (($whichColumn < ($column + $columnCount)) && ($whichColumn <= $maxCol)) {
            $this->setColumnWidth($whichColumn, $columnWidth);
            if ($hidden) {
                $this->setColumnInvisible($whichColumn);
            }
            ++$whichColumn;
        }

        return $whichColumn;
    }

    private function processColumnWidths(?SimpleXMLElement $sheet, int $maxCol): void
    {
        if ((!$this->readDataOnly) && $sheet !== null && (isset($sheet->Cols))) {
            
            $defaultWidth = 0;
            $columnAttributes = $sheet->Cols->attributes();
            if ($columnAttributes !== null) {
                $defaultWidth = $columnAttributes['DefaultSizePts'] / 5.4;
            }
            $whichColumn = 0;
            foreach ($sheet->Cols->ColInfo as $columnOverride) {
                $whichColumn = $this->processColumnLoop($whichColumn, $maxCol, $columnOverride, $defaultWidth);
            }
            while ($whichColumn <= $maxCol) {
                $this->setColumnWidth($whichColumn, $defaultWidth);
                ++$whichColumn;
            }
        }
    }

    private function setRowHeight(int $whichRow, float $defaultHeight): void
    {
        $this->spreadsheet
            ->getActiveSheet()
            ->getRowDimension($whichRow)
            ->setRowHeight($defaultHeight);
    }

    private function setRowInvisible(int $whichRow): void
    {
        $this->spreadsheet
            ->getActiveSheet()
            ->getRowDimension($whichRow)
            ->setVisible(false);
    }

    private function processRowLoop(int $whichRow, int $maxRow, ?SimpleXMLElement $rowOverride, float $defaultHeight): int
    {
        $rowOverride = self::testSimpleXml($rowOverride);
        $rowAttributes = self::testSimpleXml($rowOverride->attributes());
        $row = $rowAttributes['No'];
        $rowHeight = (float) $rowAttributes['Unit'];
        $hidden = (isset($rowAttributes['Hidden'])) && ((string) $rowAttributes['Hidden'] == '1');
        $rowCount = (int) ($rowAttributes['Count'] ?? 1);
        while ($whichRow < $row) {
            ++$whichRow;
            $this->setRowHeight($whichRow, $defaultHeight);
        }
        while (($whichRow < ($row + $rowCount)) && ($whichRow < $maxRow)) {
            ++$whichRow;
            $this->setRowHeight($whichRow, $rowHeight);
            if ($hidden) {
                $this->setRowInvisible($whichRow);
            }
        }

        return $whichRow;
    }

    private function processRowHeights(?SimpleXMLElement $sheet, int $maxRow): void
    {
        if ((!$this->readDataOnly) && $sheet !== null && (isset($sheet->Rows))) {
            
            $defaultHeight = 0;
            $rowAttributes = $sheet->Rows->attributes();
            if ($rowAttributes !== null) {
                $defaultHeight = (float) $rowAttributes['DefaultSizePts'];
            }
            $whichRow = 0;

            foreach ($sheet->Rows->RowInfo as $rowOverride) {
                $whichRow = $this->processRowLoop($whichRow, $maxRow, $rowOverride, $defaultHeight);
            }
            
            
            
            
            
            
            
        }
    }

    private function processDefinedNames(?SimpleXMLElement $gnmXML): void
    {
        
        if ($gnmXML !== null && isset($gnmXML->Names)) {
            foreach ($gnmXML->Names->Name as $definedName) {
                $name = (string) $definedName->name;
                $value = (string) $definedName->value;
                if (stripos($value, '
                    continue;
                }

                $value = str_replace("\\'", "''", $value);
                [$worksheetName] = Worksheet::extractSheetTitle($value, true, true);
                $worksheet = $this->spreadsheet->getSheetByName($worksheetName);
                
                if ($worksheet !== null) {
                    $this->spreadsheet->addDefinedName(DefinedName::createInstance($name, $worksheet, $value));
                }
            }
        }
    }

    private function parseRichText(string $is): RichText
    {
        $value = new RichText();
        $value->createText($is);

        return $value;
    }

    private function loadCell(
        SimpleXMLElement $cell,
        string $worksheetName,
        SimpleXMLElement $cellAttributes,
        string $column,
        int $row
    ): void {
        $ValueType = $cellAttributes->ValueType;
        $ExprID = (string) $cellAttributes->ExprID;
        $rows = (int) ($cellAttributes->Rows ?? 0);
        $cols = (int) ($cellAttributes->Cols ?? 0);
        $type = DataType::TYPE_FORMULA;
        $isArrayFormula = ($rows > 0 && $cols > 0);
        $arrayFormulaRange = $isArrayFormula ? $this->getArrayFormulaRange($column, $row, $cols, $rows) : null;
        if ($ExprID > '') {
            if (((string) $cell) > '') {
                
                $this->expressions[$ExprID] = [
                    'column' => (int) $cellAttributes->Col,
                    'row' => (int) $cellAttributes->Row,
                    'formula' => (string) $cell,
                ];
            } else {
                
                $expression = $this->expressions[$ExprID];

                $cell = $this->referenceHelper->updateFormulaReferences(
                    $expression['formula'],
                    'A1',
                    $cellAttributes->Col - $expression['column'],
                    $cellAttributes->Row - $expression['row'],
                    $worksheetName
                );
            }
            $type = DataType::TYPE_FORMULA;
        } elseif ($isArrayFormula === false) {
            $vtype = (string) $ValueType;
            if (array_key_exists($vtype, self::$mappings['dataType'])) {
                $type = self::$mappings['dataType'][$vtype];
            }
            if ($vtype === '20') { 
                $cell = $cell == 'TRUE';
            }
        }

        $this->spreadsheet->getActiveSheet()->getCell($column . $row)->setValueExplicit((string) $cell, $type);
        if ($arrayFormulaRange === null) {
            $this->spreadsheet->getActiveSheet()->getCell($column . $row)->setFormulaAttributes(null);
        } else {
            $this->spreadsheet->getActiveSheet()->getCell($column . $row)->setFormulaAttributes(['t' => 'array', 'ref' => $arrayFormulaRange]);
        }
        if (isset($cellAttributes->ValueFormat)) {
            $this->spreadsheet->getActiveSheet()->getCell($column . $row)
                ->getStyle()->getNumberFormat()
                ->setFormatCode((string) $cellAttributes->ValueFormat);
        }
    }

    private function getArrayFormulaRange(string $column, int $row, int $cols, int $rows): string
    {
        $arrayFormulaRange = $column . $row;
        $arrayFormulaRange .= ':'
            . Coordinate::stringFromColumnIndex(
                Coordinate::columnIndexFromString($column)
                + $cols - 1
            )
            . (string) ($row + $rows - 1);

        return $arrayFormulaRange;
    }
}
