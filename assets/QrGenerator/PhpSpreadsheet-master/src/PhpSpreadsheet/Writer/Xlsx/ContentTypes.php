<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WorksheetDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

class ContentTypes extends WriterPart
{
    
    public function writeContentTypes(Spreadsheet $spreadsheet, bool $includeCharts = false): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('Types');
        $objWriter->writeAttribute('xmlns', Namespaces::CONTENT_TYPES);

        
        $this->writeOverrideContentType($objWriter, '/xl/theme/theme1.xml', 'application/vnd.openxmlformats-officedocument.theme+xml');

        
        $this->writeOverrideContentType($objWriter, '/xl/styles.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml');

        
        $this->writeDefaultContentType($objWriter, 'rels', 'application/vnd.openxmlformats-package.relationships+xml');

        
        $this->writeDefaultContentType($objWriter, 'xml', 'application/xml');

        
        $this->writeDefaultContentType($objWriter, 'vml', 'application/vnd.openxmlformats-officedocument.vmlDrawing');

        
        if ($spreadsheet->hasMacros()) { 
            
            $this->writeOverrideContentType($objWriter, '/xl/workbook.xml', 'application/vnd.ms-excel.sheet.macroEnabled.main+xml');
            
            
            $this->writeOverrideContentType($objWriter, '/xl/vbaProject.bin', 'application/vnd.ms-office.vbaProject');
            if ($spreadsheet->hasMacrosCertificate()) {
                
                
                $this->writeOverrideContentType($objWriter, '/xl/vbaProjectSignature.bin', 'application/vnd.ms-office.vbaProjectSignature');
            }
        } else {
            
            $this->writeOverrideContentType($objWriter, '/xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml');
        }

        
        $this->writeOverrideContentType($objWriter, '/docProps/app.xml', 'application/vnd.openxmlformats-officedocument.extended-properties+xml');

        $this->writeOverrideContentType($objWriter, '/docProps/core.xml', 'application/vnd.openxmlformats-package.core-properties+xml');

        $customPropertyList = $spreadsheet->getProperties()->getCustomProperties();
        if (!empty($customPropertyList)) {
            $this->writeOverrideContentType($objWriter, '/docProps/custom.xml', 'application/vnd.openxmlformats-officedocument.custom-properties+xml');
        }

        
        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            $this->writeOverrideContentType($objWriter, '/xl/worksheets/sheet' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml');
        }

        
        $this->writeOverrideContentType($objWriter, '/xl/sharedStrings.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml');

        
        $table = 1;
        for ($i = 0; $i < $sheetCount; ++$i) {
            $tableCount = $spreadsheet->getSheet($i)->getTableCollection()->count();

            for ($t = 1; $t <= $tableCount; ++$t) {
                $this->writeOverrideContentType($objWriter, '/xl/tables/table' . $table++ . '.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml');
            }
        }

        
        
        $unparsedLoadedData = $spreadsheet->getUnparsedLoadedData();
        $chart = 1;
        for ($i = 0; $i < $sheetCount; ++$i) {
            $drawings = $spreadsheet->getSheet($i)->getDrawingCollection();
            $drawingCount = count($drawings);
            $chartCount = ($includeCharts) ? $spreadsheet->getSheet($i)->getChartCount() : 0;
            $hasUnparsedDrawing = isset($unparsedLoadedData['sheets'][$spreadsheet->getSheet($i)->getCodeName()]['drawingOriginalIds']);

            
            if (($drawingCount > 0) || ($chartCount > 0) || $hasUnparsedDrawing) {
                $this->writeOverrideContentType($objWriter, '/xl/drawings/drawing' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.drawing+xml');
            }

            
            if ($chartCount > 0) {
                for ($c = 0; $c < $chartCount; ++$c) {
                    $this->writeOverrideContentType($objWriter, '/xl/charts/chart' . $chart++ . '.xml', 'application/vnd.openxmlformats-officedocument.drawingml.chart+xml');
                }
            }
        }

        
        for ($i = 0; $i < $sheetCount; ++$i) {
            if (count($spreadsheet->getSheet($i)->getComments()) > 0) {
                $this->writeOverrideContentType($objWriter, '/xl/comments' . ($i + 1) . '.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.comments+xml');
            }
        }

        
        $aMediaContentTypes = [];
        $mediaCount = $this->getParentWriter()->getDrawingHashTable()->count();
        for ($i = 0; $i < $mediaCount; ++$i) {
            $extension = '';
            $mimeType = '';

            $drawing = $this->getParentWriter()->getDrawingHashTable()->getByIndex($i);
            if ($drawing instanceof WorksheetDrawing && $drawing->getPath() !== '') {
                $extension = strtolower($drawing->getExtension());
                if ($drawing->getIsUrl()) {
                    $mimeType = image_type_to_mime_type($drawing->getType());
                } else {
                    $mimeType = $this->getImageMimeType($drawing->getPath());
                }
            } elseif ($drawing instanceof MemoryDrawing) {
                $extension = strtolower($drawing->getMimeType());
                $extension = explode('/', $extension);
                $extension = $extension[1];

                $mimeType = $drawing->getMimeType();
            }

            if ($mimeType !== '' && !isset($aMediaContentTypes[$extension])) {
                $aMediaContentTypes[$extension] = $mimeType;

                $this->writeDefaultContentType($objWriter, $extension, $mimeType);
            }
        }
        if ($spreadsheet->hasRibbonBinObjects()) {
            
            
            
            $tabRibbonTypes = array_diff($spreadsheet->getRibbonBinObjects('types') ?? [], array_keys($aMediaContentTypes));
            foreach ($tabRibbonTypes as $aRibbonType) {
                $mimeType = 'image/.' . $aRibbonType; 
                $this->writeDefaultContentType($objWriter, $aRibbonType, $mimeType);
            }
        }
        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            if (count($spreadsheet->getSheet($i)->getHeaderFooter()->getImages()) > 0) {
                foreach ($spreadsheet->getSheet($i)->getHeaderFooter()->getImages() as $image) {
                    if ($image->getPath() !== '' && !isset($aMediaContentTypes[strtolower($image->getExtension())])) {
                        $aMediaContentTypes[strtolower($image->getExtension())] = $this->getImageMimeType($image->getPath());

                        $this->writeDefaultContentType($objWriter, strtolower($image->getExtension()), $aMediaContentTypes[strtolower($image->getExtension())]);
                    }
                }
            }

            if (count($spreadsheet->getSheet($i)->getComments()) > 0) {
                foreach ($spreadsheet->getSheet($i)->getComments() as $comment) {
                    if (!$comment->hasBackgroundImage()) {
                        continue;
                    }

                    $bgImage = $comment->getBackgroundImage();
                    $bgImageExtentionKey = strtolower($bgImage->getImageFileExtensionForSave(false));

                    if (!isset($aMediaContentTypes[$bgImageExtentionKey])) {
                        $aMediaContentTypes[$bgImageExtentionKey] = $bgImage->getImageMimeType();

                        $this->writeDefaultContentType($objWriter, $bgImageExtentionKey, $aMediaContentTypes[$bgImageExtentionKey]);
                    }
                }
            }

            $bgImage = $spreadsheet->getSheet($i)->getBackgroundImage();
            $mimeType = $spreadsheet->getSheet($i)->getBackgroundMime();
            $extension = $spreadsheet->getSheet($i)->getBackgroundExtension();
            if ($bgImage !== '' && !isset($aMediaContentTypes[$extension])) {
                $this->writeDefaultContentType($objWriter, $extension, $mimeType);
            }
        }

        
        if (isset($unparsedLoadedData['default_content_types'])) {
            
            $unparsedDefault = $unparsedLoadedData['default_content_types'];
            foreach ($unparsedDefault as $extName => $contentType) {
                $this->writeDefaultContentType($objWriter, $extName, $contentType);
            }
        }

        
        if (isset($unparsedLoadedData['override_content_types'])) {
            
            $unparsedOverride = $unparsedLoadedData['override_content_types'];
            foreach ($unparsedOverride as $partName => $overrideType) {
                $this->writeOverrideContentType($objWriter, $partName, $overrideType);
            }
        }

        
        if ($this->getParentWriter()->useDynamicArrays()) {
            $this->writeOverrideContentType($objWriter, '/xl/metadata.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheetMetadata+xml');
        }

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    private static int $three = 3; 

    
    private function getImageMimeType(string $filename): string
    {
        if (File::fileExists($filename)) {
            $image = getimagesize($filename);

            return image_type_to_mime_type((is_array($image) && count($image) >= self::$three) ? $image[2] : 0);
        }

        throw new WriterException("File $filename does not exist");
    }

    
    private function writeDefaultContentType(XMLWriter $objWriter, string $partName, string $contentType): void
    {
        if ($partName != '' && $contentType != '') {
            
            $objWriter->startElement('Default');
            $objWriter->writeAttribute('Extension', $partName);
            $objWriter->writeAttribute('ContentType', $contentType);
            $objWriter->endElement();
        } else {
            throw new WriterException('Invalid parameters passed.');
        }
    }

    
    private function writeOverrideContentType(XMLWriter $objWriter, string $partName, string $contentType): void
    {
        if ($partName != '' && $contentType != '') {
            
            $objWriter->startElement('Override');
            $objWriter->writeAttribute('PartName', $partName);
            $objWriter->writeAttribute('ContentType', $contentType);
            $objWriter->endElement();
        } else {
            throw new WriterException('Invalid parameters passed.');
        }
    }
}
