<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\DefinedNames as DefinedNamesWriter;

class Workbook extends WriterPart
{
    
    public function writeWorkbook(Spreadsheet $spreadsheet, bool $preCalculateFormulas = false, ?bool $forceFullCalc = null): string
    {
        
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('workbook');
        $objWriter->writeAttribute('xml:space', 'preserve');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);
        $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);

        
        $this->writeFileVersion($objWriter);

        
        $this->writeWorkbookPr($objWriter, $spreadsheet);

        
        $this->writeWorkbookProtection($objWriter, $spreadsheet);

        
        if ($this->getParentWriter()->getOffice2003Compatibility() === false) {
            $this->writeBookViews($objWriter, $spreadsheet);
        }

        
        $this->writeSheets($objWriter, $spreadsheet);

        
        (new DefinedNamesWriter($objWriter, $spreadsheet))->write();

        
        $this->writeCalcPr($objWriter, $preCalculateFormulas, $forceFullCalc);

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeFileVersion(XMLWriter $objWriter): void
    {
        $objWriter->startElement('fileVersion');
        $objWriter->writeAttribute('appName', 'xl');
        $objWriter->writeAttribute('lastEdited', '4');
        $objWriter->writeAttribute('lowestEdited', '4');
        $objWriter->writeAttribute('rupBuild', '4505');
        $objWriter->endElement();
    }

    
    private function writeWorkbookPr(XMLWriter $objWriter, Spreadsheet $spreadsheet): void
    {
        $objWriter->startElement('workbookPr');

        if ($spreadsheet->getExcelCalendar() === Date::CALENDAR_MAC_1904) {
            $objWriter->writeAttribute('date1904', '1');
        }

        $objWriter->writeAttribute('codeName', 'ThisWorkbook');

        $objWriter->endElement();
    }

    
    private function writeBookViews(XMLWriter $objWriter, Spreadsheet $spreadsheet): void
    {
        
        $objWriter->startElement('bookViews');

        
        $objWriter->startElement('workbookView');

        $objWriter->writeAttribute('activeTab', (string) $spreadsheet->getActiveSheetIndex());
        $objWriter->writeAttribute('autoFilterDateGrouping', ($spreadsheet->getAutoFilterDateGrouping() ? 'true' : 'false'));
        $objWriter->writeAttribute('firstSheet', (string) $spreadsheet->getFirstSheetIndex());
        $objWriter->writeAttribute('minimized', ($spreadsheet->getMinimized() ? 'true' : 'false'));
        $objWriter->writeAttribute('showHorizontalScroll', ($spreadsheet->getShowHorizontalScroll() ? 'true' : 'false'));
        $objWriter->writeAttribute('showSheetTabs', ($spreadsheet->getShowSheetTabs() ? 'true' : 'false'));
        $objWriter->writeAttribute('showVerticalScroll', ($spreadsheet->getShowVerticalScroll() ? 'true' : 'false'));
        $objWriter->writeAttribute('tabRatio', (string) $spreadsheet->getTabRatio());
        $objWriter->writeAttribute('visibility', $spreadsheet->getVisibility());

        $objWriter->endElement();

        $objWriter->endElement();
    }

    
    private function writeWorkbookProtection(XMLWriter $objWriter, Spreadsheet $spreadsheet): void
    {
        if ($spreadsheet->getSecurity()->isSecurityEnabled()) {
            $objWriter->startElement('workbookProtection');
            $objWriter->writeAttribute('lockRevision', ($spreadsheet->getSecurity()->getLockRevision() ? 'true' : 'false'));
            $objWriter->writeAttribute('lockStructure', ($spreadsheet->getSecurity()->getLockStructure() ? 'true' : 'false'));
            $objWriter->writeAttribute('lockWindows', ($spreadsheet->getSecurity()->getLockWindows() ? 'true' : 'false'));

            if ($spreadsheet->getSecurity()->getRevisionsPassword() != '') {
                $objWriter->writeAttribute('revisionsPassword', $spreadsheet->getSecurity()->getRevisionsPassword());
            }

            if ($spreadsheet->getSecurity()->getWorkbookPassword() != '') {
                $objWriter->writeAttribute('workbookPassword', $spreadsheet->getSecurity()->getWorkbookPassword());
            }

            $objWriter->endElement();
        }
    }

    
    private function writeCalcPr(XMLWriter $objWriter, bool $preCalculateFormulas, ?bool $forceFullCalc): void
    {
        $objWriter->startElement('calcPr');

        
        
        
        $objWriter->writeAttribute('calcId', '999999');
        $objWriter->writeAttribute('calcMode', 'auto');
        
        $objWriter->writeAttribute('calcCompleted', ($preCalculateFormulas) ? '1' : '0');
        $objWriter->writeAttribute('fullCalcOnLoad', ($preCalculateFormulas) ? '0' : '1');
        if ($forceFullCalc === null) {
            $objWriter->writeAttribute('forceFullCalc', $preCalculateFormulas ? '0' : '1');
        } else {
            $objWriter->writeAttribute('forceFullCalc', $forceFullCalc ? '1' : '0');
        }

        $objWriter->endElement();
    }

    
    private function writeSheets(XMLWriter $objWriter, Spreadsheet $spreadsheet): void
    {
        
        $objWriter->startElement('sheets');
        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            
            $this->writeSheet(
                $objWriter,
                $spreadsheet->getSheet($i)->getTitle(),
                ($i + 1),
                ($i + 1 + 3),
                $spreadsheet->getSheet($i)->getSheetState()
            );
        }

        $objWriter->endElement();
    }

    
    private function writeSheet(XMLWriter $objWriter, string $worksheetName, int $worksheetId = 1, int $relId = 1, string $sheetState = 'visible'): void
    {
        if ($worksheetName != '') {
            
            $objWriter->startElement('sheet');
            $objWriter->writeAttribute('name', $worksheetName);
            $objWriter->writeAttribute('sheetId', (string) $worksheetId);
            if ($sheetState !== 'visible' && $sheetState != '') {
                $objWriter->writeAttribute('state', $sheetState);
            }
            $objWriter->writeAttribute('r:id', 'rId' . $relId);
            $objWriter->endElement();
        } else {
            throw new WriterException('Invalid parameters passed.');
        }
    }
}
