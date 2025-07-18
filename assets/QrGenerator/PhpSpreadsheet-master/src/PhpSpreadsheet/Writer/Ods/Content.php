<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Ods;

use Composer\Pcre\Preg;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalculationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\RowCellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Cell\Comment;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Cell\Style;


class Content extends WriterPart
{
    private Formula $formulaConvertor;

    
    public function __construct(Ods $writer)
    {
        parent::__construct($writer);

        $this->formulaConvertor = new Formula($this->getParentWriter()->getSpreadsheet()->getDefinedNames());
    }

    
    public function write(): string
    {
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8');

        
        $objWriter->startElement('office:document-content');
        $objWriter->writeAttribute('xmlns:office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
        $objWriter->writeAttribute('xmlns:style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
        $objWriter->writeAttribute('xmlns:text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $objWriter->writeAttribute('xmlns:table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $objWriter->writeAttribute('xmlns:draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
        $objWriter->writeAttribute('xmlns:fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');
        $objWriter->writeAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $objWriter->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $objWriter->writeAttribute('xmlns:meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
        $objWriter->writeAttribute('xmlns:number', 'urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0');
        $objWriter->writeAttribute('xmlns:presentation', 'urn:oasis:names:tc:opendocument:xmlns:presentation:1.0');
        $objWriter->writeAttribute('xmlns:svg', 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0');
        $objWriter->writeAttribute('xmlns:chart', 'urn:oasis:names:tc:opendocument:xmlns:chart:1.0');
        $objWriter->writeAttribute('xmlns:dr3d', 'urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0');
        $objWriter->writeAttribute('xmlns:math', 'http://www.w3.org/1998/Math/MathML');
        $objWriter->writeAttribute('xmlns:form', 'urn:oasis:names:tc:opendocument:xmlns:form:1.0');
        $objWriter->writeAttribute('xmlns:script', 'urn:oasis:names:tc:opendocument:xmlns:script:1.0');
        $objWriter->writeAttribute('xmlns:ooo', 'http://openoffice.org/2004/office');
        $objWriter->writeAttribute('xmlns:ooow', 'http://openoffice.org/2004/writer');
        $objWriter->writeAttribute('xmlns:oooc', 'http://openoffice.org/2004/calc');
        $objWriter->writeAttribute('xmlns:dom', 'http://www.w3.org/2001/xml-events');
        $objWriter->writeAttribute('xmlns:xforms', 'http://www.w3.org/2002/xforms');
        $objWriter->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $objWriter->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $objWriter->writeAttribute('xmlns:rpt', 'http://openoffice.org/2005/report');
        $objWriter->writeAttribute('xmlns:of', 'urn:oasis:names:tc:opendocument:xmlns:of:1.2');
        $objWriter->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $objWriter->writeAttribute('xmlns:grddl', 'http://www.w3.org/2003/g/data-view
        $objWriter->writeAttribute('xmlns:tableooo', 'http://openoffice.org/2009/table');
        $objWriter->writeAttribute('xmlns:field', 'urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0');
        $objWriter->writeAttribute('xmlns:formx', 'urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:form:1.0');
        $objWriter->writeAttribute('xmlns:css3t', 'http://www.w3.org/TR/css3-text/');
        $objWriter->writeAttribute('office:version', '1.2');

        $objWriter->writeElement('office:scripts');
        $objWriter->writeElement('office:font-face-decls');

        
        $objWriter->startElement('office:automatic-styles');
        $this->writeXfStyles($objWriter, $this->getParentWriter()->getSpreadsheet());
        $objWriter->endElement();

        $objWriter->startElement('office:body');
        $objWriter->startElement('office:spreadsheet');
        $objWriter->writeElement('table:calculation-settings');

        $this->writeSheets($objWriter);

        (new AutoFilters($objWriter, $this->getParentWriter()->getSpreadsheet()))->write();
        
        (new NamedExpressions($objWriter, $this->getParentWriter()->getSpreadsheet(), $this->formulaConvertor))->write();

        $objWriter->endElement();
        $objWriter->endElement();
        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    private function writeSheets(XMLWriter $objWriter): void
    {
        $spreadsheet = $this->getParentWriter()->getSpreadsheet();
        $sheetCount = $spreadsheet->getSheetCount();
        for ($sheetIndex = 0; $sheetIndex < $sheetCount; ++$sheetIndex) {
            $spreadsheet->getSheet($sheetIndex)->calculateArrays($this->getParentWriter()->getPreCalculateFormulas());
            $objWriter->startElement('table:table');
            $objWriter->writeAttribute('table:name', $spreadsheet->getSheet($sheetIndex)->getTitle());
            $objWriter->writeAttribute('table:style-name', Style::TABLE_STYLE_PREFIX . (string) ($sheetIndex + 1));
            $objWriter->writeElement('office:forms');
            $lastColumn = 0;
            foreach ($spreadsheet->getSheet($sheetIndex)->getColumnDimensions() as $columnDimension) {
                $thisColumn = $columnDimension->getColumnNumeric();
                $emptyColumns = $thisColumn - $lastColumn - 1;
                if ($emptyColumns > 0) {
                    $objWriter->startElement('table:table-column');
                    $objWriter->writeAttribute('table:number-columns-repeated', (string) $emptyColumns);
                    $objWriter->endElement();
                }
                $lastColumn = $thisColumn;
                $objWriter->startElement('table:table-column');
                $objWriter->writeAttribute(
                    'table:style-name',
                    sprintf('%s_%d_%d', Style::COLUMN_STYLE_PREFIX, $sheetIndex, $columnDimension->getColumnNumeric())
                );
                $objWriter->writeAttribute('table:default-cell-style-name', 'ce0');
                $objWriter->endElement();
            }
            $this->writeRows($objWriter, $spreadsheet->getSheet($sheetIndex), $sheetIndex);
            $objWriter->endElement();
        }
    }

    
    private function writeRows(XMLWriter $objWriter, Worksheet $sheet, int $sheetIndex): void
    {
        $spanRow = 0;
        $rows = $sheet->getRowIterator();
        foreach ($rows as $row) {
            $cellIterator = $row->getCellIterator(iterateOnlyExistingCells: true);
            $cellIterator->rewind();
            $rowStyleExists = $sheet->rowDimensionExists($row->getRowIndex()) && $sheet->getRowDimension($row->getRowIndex())->getRowHeight() > 0;
            if ($cellIterator->valid() || $rowStyleExists) {
                if ($spanRow) {
                    $objWriter->startElement('table:table-row');
                    $objWriter->writeAttribute(
                        'table:number-rows-repeated',
                        (string) $spanRow
                    );
                    $objWriter->endElement();
                    $spanRow = 0;
                }
                $objWriter->startElement('table:table-row');
                if ($rowStyleExists) {
                    $objWriter->writeAttribute(
                        'table:style-name',
                        sprintf('%s_%d_%d', Style::ROW_STYLE_PREFIX, $sheetIndex, $row->getRowIndex())
                    );
                }
                $this->writeCells($objWriter, $cellIterator);
                $objWriter->endElement();
            } else {
                ++$spanRow;
            }
        }
    }

    
    private function writeCells(XMLWriter $objWriter, RowCellIterator $cells): void
    {
        $prevColumn = -1;
        foreach ($cells as $cell) {
            
            $column = Coordinate::columnIndexFromString($cell->getColumn()) - 1;
            $attributes = $cell->getFormulaAttributes() ?? [];

            $this->writeCellSpan($objWriter, $column, $prevColumn);
            $objWriter->startElement('table:table-cell');
            $this->writeCellMerge($objWriter, $cell);

            
            $style = $cell->getXfIndex();
            $objWriter->writeAttribute('table:style-name', Style::CELL_STYLE_PREFIX . $style);

            switch ($cell->getDataType()) {
                case DataType::TYPE_BOOL:
                    $objWriter->writeAttribute('office:value-type', 'boolean');
                    $objWriter->writeAttribute('office:boolean-value', $cell->getValue() ? 'true' : 'false');
                    $objWriter->writeElement('text:p', Calculation::getInstance()->getLocaleBoolean($cell->getValue() ? 'TRUE' : 'FALSE'));

                    break;
                case DataType::TYPE_ERROR:
                    $objWriter->writeAttribute('table:formula', 'of:=
                    $objWriter->writeAttribute('office:value-type', 'string');
                    $objWriter->writeAttribute('office:string-value', '');
                    $objWriter->writeElement('text:p', '

                    break;
                case DataType::TYPE_FORMULA:
                    $formulaValue = $cell->getValueString();
                    if ($this->getParentWriter()->getPreCalculateFormulas()) {
                        try {
                            $formulaValue = $cell->getCalculatedValueString();
                        } catch (CalculationException $e) {
                            
                        }
                    }
                    if (isset($attributes['ref'])) {
                        if (Preg::isMatch('/^([A-Z]{1,3})([0-9]{1,7})(:([A-Z]{1,3})([0-9]{1,7}))?$/', (string) $attributes['ref'], $matches)) {
                            $matrixRowSpan = 1;
                            $matrixColSpan = 1;
                            if (isset($matches[3])) {
                                $minRow = (int) $matches[2];
                                $maxRow = (int) $matches[5];
                                $matrixRowSpan = $maxRow - $minRow + 1;
                                $minCol = Coordinate::columnIndexFromString($matches[1]);
                                $maxCol = Coordinate::columnIndexFromString($matches[4]);
                                $matrixColSpan = $maxCol - $minCol + 1;
                            }
                            $objWriter->writeAttribute('table:number-matrix-columns-spanned', "$matrixColSpan");
                            $objWriter->writeAttribute('table:number-matrix-rows-spanned', "$matrixRowSpan");
                        }
                    }
                    $objWriter->writeAttribute('table:formula', $this->formulaConvertor->convertFormula($cell->getValueString()));
                    if (is_numeric($formulaValue)) {
                        $objWriter->writeAttribute('office:value-type', 'float');
                    } else {
                        $objWriter->writeAttribute('office:value-type', 'string');
                    }
                    $objWriter->writeAttribute('office:value', $formulaValue);
                    $objWriter->writeElement('text:p', $formulaValue);

                    break;
                case DataType::TYPE_NUMERIC:
                    $objWriter->writeAttribute('office:value-type', 'float');
                    $objWriter->writeAttribute('office:value', $cell->getValueString());
                    $objWriter->writeElement('text:p', $cell->getValueString());

                    break;
                case DataType::TYPE_INLINE:
                    
                case DataType::TYPE_STRING:
                    $objWriter->writeAttribute('office:value-type', 'string');
                    $url = $cell->getHyperlink()->getUrl();
                    if (empty($url)) {
                        $objWriter->writeElement('text:p', $cell->getValueString());
                    } else {
                        $objWriter->startElement('text:p');
                        $objWriter->startElement('text:a');
                        $sheets = 'sheet://';
                        $lensheets = strlen($sheets);
                        if (substr($url, 0, $lensheets) === $sheets) {
                            $url = '
                        }
                        $objWriter->writeAttribute('xlink:href', $url);
                        $objWriter->writeAttribute('xlink:type', 'simple');
                        $objWriter->text($cell->getValueString());
                        $objWriter->endElement(); 
                        $objWriter->endElement(); 
                    }

                    break;
            }
            Comment::write($objWriter, $cell);
            $objWriter->endElement();
            $prevColumn = $column;
        }
    }

    
    private function writeCellSpan(XMLWriter $objWriter, int $curColumn, int $prevColumn): void
    {
        $diff = $curColumn - $prevColumn - 1;
        if (1 === $diff) {
            $objWriter->writeElement('table:table-cell');
        } elseif ($diff > 1) {
            $objWriter->startElement('table:table-cell');
            $objWriter->writeAttribute('table:number-columns-repeated', (string) $diff);
            $objWriter->endElement();
        }
    }

    
    private function writeXfStyles(XMLWriter $writer, Spreadsheet $spreadsheet): void
    {
        $styleWriter = new Style($writer);

        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            $worksheet = $spreadsheet->getSheet($i);
            $styleWriter->writeTableStyle($worksheet, $i + 1);

            $worksheet->calculateColumnWidths();
            foreach ($worksheet->getColumnDimensions() as $columnDimension) {
                if ($columnDimension->getWidth() !== -1.0) {
                    $styleWriter->writeColumnStyles($columnDimension, $i);
                }
            }
        }
        for ($i = 0; $i < $sheetCount; ++$i) {
            $worksheet = $spreadsheet->getSheet($i);
            foreach ($worksheet->getRowDimensions() as $rowDimension) {
                if ($rowDimension->getRowHeight() > 0.0) {
                    $styleWriter->writeRowStyles($rowDimension, $i);
                }
            }
        }

        foreach ($spreadsheet->getCellXfCollection() as $style) {
            $styleWriter->write($style);
        }
    }

    
    private function writeCellMerge(XMLWriter $objWriter, Cell $cell): void
    {
        if (!$cell->isMergeRangeValueCell()) {
            return;
        }

        $mergeRange = Coordinate::splitRange((string) $cell->getMergeRange());
        [$startCell, $endCell] = $mergeRange[0];
        $start = Coordinate::coordinateFromString($startCell);
        $end = Coordinate::coordinateFromString($endCell);
        $columnSpan = Coordinate::columnIndexFromString($end[0]) - Coordinate::columnIndexFromString($start[0]) + 1;
        $rowSpan = ((int) $end[1]) - ((int) $start[1]) + 1;

        $objWriter->writeAttribute('table:number-columns-spanned', (string) $columnSpan);
        $objWriter->writeAttribute('table:number-rows-spanned', (string) $rowSpan);
    }
}
