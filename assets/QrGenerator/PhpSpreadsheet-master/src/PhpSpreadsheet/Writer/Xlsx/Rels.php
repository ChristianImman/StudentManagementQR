<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

class Rels extends WriterPart
{
    
    public function writeRelationships(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        $customPropertyList = $spreadsheet->getProperties()->getCustomProperties();
        if (!empty($customPropertyList)) {
            
            $this->writeRelationship(
                $objWriter,
                4,
                Namespaces::RELATIONSHIPS_CUSTOM_PROPERTIES,
                'docProps/custom.xml'
            );
        }

        
        $this->writeRelationship(
            $objWriter,
            3,
            Namespaces::RELATIONSHIPS_EXTENDED_PROPERTIES,
            'docProps/app.xml'
        );

        
        $this->writeRelationship(
            $objWriter,
            2,
            Namespaces::CORE_PROPERTIES,
            'docProps/core.xml'
        );

        
        $this->writeRelationship(
            $objWriter,
            1,
            Namespaces::OFFICE_DOCUMENT,
            'xl/workbook.xml'
        );
        
        $target = $spreadsheet->getRibbonXMLData('target');
        if ($spreadsheet->hasRibbon()) {
            $this->writeRelationShip(
                $objWriter,
                5,
                Namespaces::EXTENSIBILITY,
                is_string($target) ? $target : ''
            );
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    public function writeWorkbookRelationships(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        
        $this->writeRelationship(
            $objWriter,
            1,
            Namespaces::STYLES,
            'styles.xml'
        );

        
        $this->writeRelationship(
            $objWriter,
            2,
            Namespaces::THEME2,
            'theme/theme1.xml'
        );

        
        $this->writeRelationship(
            $objWriter,
            3,
            Namespaces::SHARED_STRINGS,
            'sharedStrings.xml'
        );

        
        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            $this->writeRelationship(
                $objWriter,
                ($i + 1 + 3),
                Namespaces::WORKSHEET,
                'worksheets/sheet' . ($i + 1) . '.xml'
            );
        }
        
        
        if ($spreadsheet->hasMacros()) {
            $this->writeRelationShip(
                $objWriter,
                ($i + 1 + 3),
                Namespaces::VBA,
                'vbaProject.bin'
            );
            ++$i; 
        }

        
        if ($this->getParentWriter()->useDynamicArrays()) {
            $this->writeRelationShip(
                $objWriter,
                ($i + 1 + 3),
                Namespaces::RELATIONSHIPS_METADATA,
                'metadata.xml'
            );
            ++$i; 
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    public function writeWorksheetRelationships(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, int $worksheetId = 1, bool $includeCharts = false, int $tableRef = 1, array &$zipContent = []): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        
        $drawingOriginalIds = [];
        
        $unparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        if (isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingOriginalIds'])) {
            $drawingOriginalIds = $unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingOriginalIds'];
        }

        if ($includeCharts) {
            $charts = $worksheet->getChartCollection();
        } else {
            $charts = [];
        }

        if (($worksheet->getDrawingCollection()->count() > 0) || (count($charts) > 0) || $drawingOriginalIds) {
            $rId = 1;

            
            
            
            reset($drawingOriginalIds);
            $relPath = key($drawingOriginalIds);
            if (isset($drawingOriginalIds[$relPath])) {
                $rId = (int) (substr($drawingOriginalIds[$relPath], 3));
            }

            
            $relPath = '../drawings/drawing' . $worksheetId . '.xml';
            $this->writeRelationship(
                $objWriter,
                $rId,
                Namespaces::RELATIONSHIPS_DRAWING,
                $relPath
            );
        }

        $backgroundImage = $worksheet->getBackgroundImage();
        if ($backgroundImage !== '') {
            $rId = 'Bg';
            $uniqueName = md5(mt_rand(0, 9999) . time() . mt_rand(0, 9999));
            $relPath = "../media/$uniqueName." . $worksheet->getBackgroundExtension();
            $this->writeRelationship(
                $objWriter,
                $rId,
                Namespaces::IMAGE,
                $relPath
            );
            $zipContent["xl/media/$uniqueName." . $worksheet->getBackgroundExtension()] = $backgroundImage;
        }

        
        $i = 1;
        foreach ($worksheet->getHyperlinkCollection() as $hyperlink) {
            if (!$hyperlink->isInternal()) {
                $this->writeRelationship(
                    $objWriter,
                    '_hyperlink_' . $i,
                    Namespaces::HYPERLINK,
                    $hyperlink->getUrl(),
                    'External'
                );

                ++$i;
            }
        }

        
        $i = 1;
        if (count($worksheet->getComments()) > 0 || isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['legacyDrawing'])) {
            $this->writeRelationship(
                $objWriter,
                '_comments_vml' . $i,
                Namespaces::VML,
                '../drawings/vmlDrawing' . $worksheetId . '.vml'
            );
        }

        if (count($worksheet->getComments()) > 0) {
            $this->writeRelationship(
                $objWriter,
                '_comments' . $i,
                Namespaces::COMMENTS,
                '../comments' . $worksheetId . '.xml'
            );
        }

        
        $tableCount = $worksheet->getTableCollection()->count();
        for ($i = 1; $i <= $tableCount; ++$i) {
            $this->writeRelationship(
                $objWriter,
                '_table_' . $i,
                Namespaces::RELATIONSHIPS_TABLE,
                '../tables/table' . $tableRef++ . '.xml'
            );
        }

        
        $i = 1;
        if (count($worksheet->getHeaderFooter()->getImages()) > 0) {
            $this->writeRelationship(
                $objWriter,
                '_headerfooter_vml' . $i,
                Namespaces::VML,
                '../drawings/vmlDrawingHF' . $worksheetId . '.vml'
            );
        }

        $this->writeUnparsedRelationship($worksheet, $objWriter, 'ctrlProps', Namespaces::RELATIONSHIPS_CTRLPROP);
        $this->writeUnparsedRelationship($worksheet, $objWriter, 'vmlDrawings', Namespaces::VML);
        $this->writeUnparsedRelationship($worksheet, $objWriter, 'printerSettings', Namespaces::RELATIONSHIPS_PRINTER_SETTINGS);

        $objWriter->endElement();

        return $objWriter->getData();
    }

    private function writeUnparsedRelationship(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, XMLWriter $objWriter, string $relationship, string $type): void
    {
        
        $unparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        if (!isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()][$relationship])) {
            return;
        }

        foreach ($unparsedLoadedData['sheets'][$worksheet->getCodeName()][$relationship] as $rId => $value) {
            if (!str_starts_with($rId, '_headerfooter_vml')) {
                
                $this->writeRelationship(
                    $objWriter,
                    $rId,
                    $type,
                    $value['relFilePath']
                );
            }
        }
    }

    
    public function writeDrawingRelationships(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, int &$chartRef, bool $includeCharts = false): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        
        $i = 1;
        $iterator = $worksheet->getDrawingCollection()->getIterator();
        while ($iterator->valid()) {
            $drawing = $iterator->current();
            if (
                $drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing
                || $drawing instanceof MemoryDrawing
            ) {
                
                $this->writeRelationship(
                    $objWriter,
                    $i,
                    Namespaces::IMAGE,
                    '../media/' . $drawing->getIndexedFilename()
                );

                $i = $this->writeDrawingHyperLink($objWriter, $drawing, $i);
            }

            $iterator->next();
            ++$i;
        }

        if ($includeCharts) {
            
            $chartCount = $worksheet->getChartCount();
            if ($chartCount > 0) {
                for ($c = 0; $c < $chartCount; ++$c) {
                    $this->writeRelationship(
                        $objWriter,
                        $i++,
                        Namespaces::RELATIONSHIPS_CHART,
                        '../charts/chart' . ++$chartRef . '.xml'
                    );
                }
            }
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    public function writeHeaderFooterDrawingRelationships(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        
        foreach ($worksheet->getHeaderFooter()->getImages() as $key => $value) {
            
            $this->writeRelationship(
                $objWriter,
                $key,
                Namespaces::IMAGE,
                '../media/' . $value->getIndexedFilename()
            );
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    public function writeVMLDrawingRelationships(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Relationships');
        $objWriter->writeAttribute('xmlns', Namespaces::RELATIONSHIPS);

        
        foreach ($worksheet->getComments() as $comment) {
            if (!$comment->hasBackgroundImage()) {
                continue;
            }

            $bgImage = $comment->getBackgroundImage();
            $this->writeRelationship(
                $objWriter,
                $bgImage->getImageIndex(),
                Namespaces::IMAGE,
                '../media/' . $bgImage->getMediaFilename()
            );
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    private function writeRelationship(XMLWriter $objWriter, $id, string $type, string $target, string $targetMode = ''): void
    {
        if ($type != '' && $target != '') {
            
            $objWriter->startElement('Relationship');
            $objWriter->writeAttribute('Id', 'rId' . $id);
            $objWriter->writeAttribute('Type', $type);
            $objWriter->writeAttribute('Target', $target);

            if ($targetMode != '') {
                $objWriter->writeAttribute('TargetMode', $targetMode);
            }

            $objWriter->endElement();
        } else {
            throw new WriterException('Invalid parameters passed.');
        }
    }

    private function writeDrawingHyperLink(XMLWriter $objWriter, BaseDrawing $drawing, int $i): int
    {
        if ($drawing->getHyperlink() === null) {
            return $i;
        }

        ++$i;
        $this->writeRelationship(
            $objWriter,
            $i,
            Namespaces::HYPERLINK,
            $drawing->getHyperlink()->getUrl(),
            $drawing->getHyperlink()->getTypeHyperlink()
        );

        return $i;
    }
}
