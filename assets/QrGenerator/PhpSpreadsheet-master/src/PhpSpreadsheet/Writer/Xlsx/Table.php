<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Worksheet\Table as WorksheetTable;

class Table extends WriterPart
{
    
    public function writeTable(WorksheetTable $table, int $tableRef): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $name = 'Table' . $tableRef;
        $range = $table->getRange();

        $objWriter->startElement('table');
        $objWriter->writeAttribute('xml:space', 'preserve');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);
        $objWriter->writeAttribute('id', (string) $tableRef);
        $objWriter->writeAttribute('name', $name);
        $objWriter->writeAttribute('displayName', $table->getName() ?: $name);
        $objWriter->writeAttribute('ref', $range);
        $objWriter->writeAttribute('headerRowCount', $table->getShowHeaderRow() ? '1' : '0');
        $objWriter->writeAttribute('totalsRowCount', $table->getShowTotalsRow() ? '1' : '0');

        
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($table->getRange());

        
        if ($table->getShowHeaderRow() && $table->getAllowFilter() === true) {
            $objWriter->startElement('autoFilter');
            $objWriter->writeAttribute('ref', $range);
            foreach (range($rangeStart[0], $rangeEnd[0]) as $offset => $columnIndex) {
                $column = $table->getColumnByOffset($offset);

                if (!$column->getShowFilterButton()) {
                    $objWriter->startElement('filterColumn');
                    $objWriter->writeAttribute('colId', (string) $offset);
                    $objWriter->writeAttribute('hiddenButton', '1');
                    $objWriter->endElement();
                } else {
                    $column = $table->getAutoFilter()->getColumnByOffset($offset);
                    AutoFilter::writeAutoFilterColumn($objWriter, $column, $offset);
                }
            }
            $objWriter->endElement(); 
        }

        
        $objWriter->startElement('tableColumns');
        $objWriter->writeAttribute('count', (string) ($rangeEnd[0] - $rangeStart[0] + 1));
        foreach (range($rangeStart[0], $rangeEnd[0]) as $offset => $columnIndex) {
            $worksheet = $table->getWorksheet();
            if (!$worksheet) {
                continue;
            }

            $column = $table->getColumnByOffset($offset);
            $cell = $worksheet->getCell([$columnIndex, $rangeStart[1]]);

            $objWriter->startElement('tableColumn');
            $objWriter->writeAttribute('id', (string) ($offset + 1));
            $objWriter->writeAttribute('name', $table->getShowHeaderRow() ? $cell->getValueString() : ('Column' . ($offset + 1)));

            if ($table->getShowTotalsRow()) {
                if ($column->getTotalsRowLabel()) {
                    $objWriter->writeAttribute('totalsRowLabel', $column->getTotalsRowLabel());
                }
                if ($column->getTotalsRowFunction()) {
                    $objWriter->writeAttribute('totalsRowFunction', $column->getTotalsRowFunction());
                }
            }
            if ($column->getColumnFormula()) {
                $objWriter->writeElement('calculatedColumnFormula', $column->getColumnFormula());
            }

            $objWriter->endElement();
        }
        $objWriter->endElement();

        
        $objWriter->startElement('tableStyleInfo');
        $objWriter->writeAttribute('name', $table->getStyle()->getTheme());
        $objWriter->writeAttribute('showFirstColumn', $table->getStyle()->getShowFirstColumn() ? '1' : '0');
        $objWriter->writeAttribute('showLastColumn', $table->getStyle()->getShowLastColumn() ? '1' : '0');
        $objWriter->writeAttribute('showRowStripes', $table->getStyle()->getShowRowStripes() ? '1' : '0');
        $objWriter->writeAttribute('showColumnStripes', $table->getStyle()->getShowColumnStripes() ? '1' : '0');
        $objWriter->endElement();

        $objWriter->endElement();

        
        return $objWriter->getData();
    }
}
