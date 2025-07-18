<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Composer\Pcre\Preg;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

class Drawing extends WriterPart
{
    
    public function writeDrawings(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, bool $includeCharts = false): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('xdr:wsDr');
        $objWriter->writeAttribute('xmlns:xdr', Namespaces::SPREADSHEET_DRAWING);
        $objWriter->writeAttribute('xmlns:a', Namespaces::DRAWINGML);

        
        $i = 1;
        $iterator = $worksheet->getDrawingCollection()->getIterator();
        while ($iterator->valid()) {
            
            $pDrawing = $iterator->current();
            $pRelationId = $i;
            $hlinkClickId = $pDrawing->getHyperlink() === null ? null : ++$i;

            $this->writeDrawing($objWriter, $pDrawing, $pRelationId, $hlinkClickId);

            $iterator->next();
            ++$i;
        }

        if ($includeCharts) {
            $chartCount = $worksheet->getChartCount();
            
            if ($chartCount > 0) {
                for ($c = 0; $c < $chartCount; ++$c) {
                    $chart = $worksheet->getChartByIndex((string) $c);
                    if ($chart !== false) {
                        $this->writeChart($objWriter, $chart, $c + $i);
                    }
                }
            }
        }

        
        
        $unparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        if (isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingAlternateContents'])) {
            foreach ($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingAlternateContents'] as $drawingAlternateContent) {
                $objWriter->writeRaw($drawingAlternateContent);
            }
        }

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    public function writeChart(XMLWriter $objWriter, \PhpOffice\PhpSpreadsheet\Chart\Chart $chart, int $relationId = -1): void
    {
        $tl = $chart->getTopLeftPosition();
        $tlColRow = Coordinate::indexesFromString($tl['cell']);
        $br = $chart->getBottomRightPosition();

        $isTwoCellAnchor = $br['cell'] !== '';
        if ($isTwoCellAnchor) {
            $brColRow = Coordinate::indexesFromString($br['cell']);

            $objWriter->startElement('xdr:twoCellAnchor');

            $objWriter->startElement('xdr:from');
            $objWriter->writeElement('xdr:col', (string) ($tlColRow[0] - 1));
            $objWriter->writeElement('xdr:colOff', self::stringEmu($tl['xOffset']));
            $objWriter->writeElement('xdr:row', (string) ($tlColRow[1] - 1));
            $objWriter->writeElement('xdr:rowOff', self::stringEmu($tl['yOffset']));
            $objWriter->endElement();
            $objWriter->startElement('xdr:to');
            $objWriter->writeElement('xdr:col', (string) ($brColRow[0] - 1));
            $objWriter->writeElement('xdr:colOff', self::stringEmu($br['xOffset']));
            $objWriter->writeElement('xdr:row', (string) ($brColRow[1] - 1));
            $objWriter->writeElement('xdr:rowOff', self::stringEmu($br['yOffset']));
            $objWriter->endElement();
        } elseif ($chart->getOneCellAnchor()) {
            $objWriter->startElement('xdr:oneCellAnchor');

            $objWriter->startElement('xdr:from');
            $objWriter->writeElement('xdr:col', (string) ($tlColRow[0] - 1));
            $objWriter->writeElement('xdr:colOff', self::stringEmu($tl['xOffset']));
            $objWriter->writeElement('xdr:row', (string) ($tlColRow[1] - 1));
            $objWriter->writeElement('xdr:rowOff', self::stringEmu($tl['yOffset']));
            $objWriter->endElement();
            $objWriter->startElement('xdr:ext');
            $objWriter->writeAttribute('cx', self::stringEmu($br['xOffset']));
            $objWriter->writeAttribute('cy', self::stringEmu($br['yOffset']));
            $objWriter->endElement();
        } else {
            $objWriter->startElement('xdr:absoluteAnchor');
            $objWriter->startElement('xdr:pos');
            $objWriter->writeAttribute('x', '0');
            $objWriter->writeAttribute('y', '0');
            $objWriter->endElement();
            $objWriter->startElement('xdr:ext');
            $objWriter->writeAttribute('cx', self::stringEmu($br['xOffset']));
            $objWriter->writeAttribute('cy', self::stringEmu($br['yOffset']));
            $objWriter->endElement();
        }

        $objWriter->startElement('xdr:graphicFrame');
        $objWriter->writeAttribute('macro', '');
        $objWriter->startElement('xdr:nvGraphicFramePr');
        $objWriter->startElement('xdr:cNvPr');
        $objWriter->writeAttribute('name', 'Chart ' . $relationId);
        $objWriter->writeAttribute('id', (string) (1025 * $relationId));
        $objWriter->endElement();
        $objWriter->startElement('xdr:cNvGraphicFramePr');
        $objWriter->startElement('a:graphicFrameLocks');
        $objWriter->endElement();
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('xdr:xfrm');
        $objWriter->startElement('a:off');
        $objWriter->writeAttribute('x', '0');
        $objWriter->writeAttribute('y', '0');
        $objWriter->endElement();
        $objWriter->startElement('a:ext');
        $objWriter->writeAttribute('cx', '0');
        $objWriter->writeAttribute('cy', '0');
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('a:graphic');
        $objWriter->startElement('a:graphicData');
        $objWriter->writeAttribute('uri', Namespaces::CHART);
        $objWriter->startElement('c:chart');
        $objWriter->writeAttribute('xmlns:c', Namespaces::CHART);
        $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);
        $objWriter->writeAttribute('r:id', 'rId' . $relationId);
        $objWriter->endElement();
        $objWriter->endElement();
        $objWriter->endElement();
        $objWriter->endElement();

        $objWriter->startElement('xdr:clientData');
        $objWriter->endElement();

        $objWriter->endElement();
    }

    
    public function writeDrawing(XMLWriter $objWriter, BaseDrawing $drawing, int $relationId = -1, ?int $hlinkClickId = null): void
    {
        if ($relationId >= 0) {
            $isTwoCellAnchor = $drawing->getCoordinates2() !== '';
            if ($isTwoCellAnchor) {
                
                $objWriter->startElement('xdr:twoCellAnchor');
                if ($drawing->validEditAs()) {
                    $objWriter->writeAttribute('editAs', $drawing->getEditAs());
                }
                
                $aCoordinates = Coordinate::indexesFromString($drawing->getCoordinates());
                $aCoordinates2 = Coordinate::indexesFromString($drawing->getCoordinates2());

                
                $objWriter->startElement('xdr:from');
                $objWriter->writeElement('xdr:col', (string) ($aCoordinates[0] - 1));
                $objWriter->writeElement('xdr:colOff', self::stringEmu($drawing->getOffsetX()));
                $objWriter->writeElement('xdr:row', (string) ($aCoordinates[1] - 1));
                $objWriter->writeElement('xdr:rowOff', self::stringEmu($drawing->getOffsetY()));
                $objWriter->endElement();

                
                $objWriter->startElement('xdr:to');
                $objWriter->writeElement('xdr:col', (string) ($aCoordinates2[0] - 1));
                $objWriter->writeElement('xdr:colOff', self::stringEmu($drawing->getOffsetX2()));
                $objWriter->writeElement('xdr:row', (string) ($aCoordinates2[1] - 1));
                $objWriter->writeElement('xdr:rowOff', self::stringEmu($drawing->getOffsetY2()));
                $objWriter->endElement();
            } else {
                
                $objWriter->startElement('xdr:oneCellAnchor');
                
                $aCoordinates = Coordinate::indexesFromString($drawing->getCoordinates());

                
                $objWriter->startElement('xdr:from');
                $objWriter->writeElement('xdr:col', (string) ($aCoordinates[0] - 1));
                $objWriter->writeElement('xdr:colOff', self::stringEmu($drawing->getOffsetX()));
                $objWriter->writeElement('xdr:row', (string) ($aCoordinates[1] - 1));
                $objWriter->writeElement('xdr:rowOff', self::stringEmu($drawing->getOffsetY()));
                $objWriter->endElement();

                
                $objWriter->startElement('xdr:ext');
                $objWriter->writeAttribute('cx', self::stringEmu($drawing->getWidth()));
                $objWriter->writeAttribute('cy', self::stringEmu($drawing->getHeight()));
                $objWriter->endElement();
            }

            
            $objWriter->startElement('xdr:pic');

            
            $objWriter->startElement('xdr:nvPicPr');

            
            $objWriter->startElement('xdr:cNvPr');
            $objWriter->writeAttribute('id', (string) $relationId);
            $objWriter->writeAttribute('name', $drawing->getName());
            $objWriter->writeAttribute('descr', $drawing->getDescription());

            
            $this->writeHyperLinkDrawing($objWriter, $hlinkClickId);

            $objWriter->endElement();

            
            $objWriter->startElement('xdr:cNvPicPr');

            
            $objWriter->startElement('a:picLocks');
            $objWriter->writeAttribute('noChangeAspect', '1');
            $objWriter->endElement();

            $objWriter->endElement();

            $objWriter->endElement();

            
            $objWriter->startElement('xdr:blipFill');

            
            $objWriter->startElement('a:blip');
            $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);
            $objWriter->writeAttribute('r:embed', 'rId' . $relationId);
            $temp = $drawing->getOpacity();
            if (is_int($temp) && $temp >= 0 && $temp <= 100000) {
                $objWriter->startElement('a:alphaModFix');
                $objWriter->writeAttribute('amt', "$temp");
                $objWriter->endElement(); 
            }
            $objWriter->endElement(); 

            $srcRect = $drawing->getSrcRect();
            if (!empty($srcRect)) {
                $objWriter->startElement('a:srcRect');
                foreach ($srcRect as $key => $value) {
                    $objWriter->writeAttribute($key, (string) $value);
                }
                $objWriter->endElement(); 
                $objWriter->startElement('a:stretch');
                $objWriter->endElement(); 
            } else {
                
                $objWriter->startElement('a:stretch');
                $objWriter->writeElement('a:fillRect', null);
                $objWriter->endElement();
            }

            $objWriter->endElement();

            
            $objWriter->startElement('xdr:spPr');

            
            $objWriter->startElement('a:xfrm');
            $objWriter->writeAttribute('rot', (string) SharedDrawing::degreesToAngle($drawing->getRotation()));
            self::writeAttributeIf($objWriter, $drawing->getFlipVertical(), 'flipV', '1');
            self::writeAttributeIf($objWriter, $drawing->getFlipHorizontal(), 'flipH', '1');
            if ($isTwoCellAnchor) {
                $objWriter->startElement('a:ext');
                $objWriter->writeAttribute('cx', self::stringEmu($drawing->getWidth()));
                $objWriter->writeAttribute('cy', self::stringEmu($drawing->getHeight()));
                $objWriter->endElement();
            }
            $objWriter->endElement();

            
            $objWriter->startElement('a:prstGeom');
            $objWriter->writeAttribute('prst', 'rect');

            
            $objWriter->writeElement('a:avLst', null);

            $objWriter->endElement();

            if ($drawing->getShadow()->getVisible()) {
                
                $objWriter->startElement('a:effectLst');

                
                $objWriter->startElement('a:outerShdw');
                $objWriter->writeAttribute('blurRad', self::stringEmu($drawing->getShadow()->getBlurRadius()));
                $objWriter->writeAttribute('dist', self::stringEmu($drawing->getShadow()->getDistance()));
                $objWriter->writeAttribute('dir', (string) SharedDrawing::degreesToAngle($drawing->getShadow()->getDirection()));
                $objWriter->writeAttribute('algn', $drawing->getShadow()->getAlignment());
                $objWriter->writeAttribute('rotWithShape', '0');

                
                $objWriter->startElement('a:srgbClr');
                $objWriter->writeAttribute('val', $drawing->getShadow()->getColor()->getRGB());

                
                $objWriter->startElement('a:alpha');
                $objWriter->writeAttribute('val', (string) ($drawing->getShadow()->getAlpha() * 1000));
                $objWriter->endElement();

                $objWriter->endElement();

                $objWriter->endElement();

                $objWriter->endElement();
            }
            $objWriter->endElement();

            $objWriter->endElement();

            
            $objWriter->writeElement('xdr:clientData', null);

            $objWriter->endElement();
        } else {
            throw new WriterException('Invalid parameters passed.');
        }
    }

    
    public function writeVMLHeaderFooterImages(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $images = $worksheet->getHeaderFooter()->getImages();

        
        $objWriter->startElement('xml');
        $objWriter->writeAttribute('xmlns:v', Namespaces::URN_VML);
        $objWriter->writeAttribute('xmlns:o', Namespaces::URN_MSOFFICE);
        $objWriter->writeAttribute('xmlns:x', Namespaces::URN_EXCEL);

        
        $objWriter->startElement('o:shapelayout');
        $objWriter->writeAttribute('v:ext', 'edit');

        
        $objWriter->startElement('o:idmap');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->writeAttribute('data', '1');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('v:shapetype');
        $objWriter->writeAttribute('id', '_x0000_t75');
        $objWriter->writeAttribute('coordsize', '21600,21600');
        $objWriter->writeAttribute('o:spt', '75');
        $objWriter->writeAttribute('o:preferrelative', 't');
        $objWriter->writeAttribute('path', 'm@4@5l@4@11@9@11@9@5xe');
        $objWriter->writeAttribute('filled', 'f');
        $objWriter->writeAttribute('stroked', 'f');

        
        $objWriter->startElement('v:stroke');
        $objWriter->writeAttribute('joinstyle', 'miter');
        $objWriter->endElement();

        
        $objWriter->startElement('v:formulas');

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'if lineDrawn pixelLineWidth 0');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'sum @0 1 0');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'sum 0 0 @1');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @2 1 2');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @3 21600 pixelWidth');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @3 21600 pixelHeight');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'sum @0 0 1');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @6 1 2');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @7 21600 pixelWidth');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'sum @8 21600 0');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'prod @7 21600 pixelHeight');
        $objWriter->endElement();

        
        $objWriter->startElement('v:f');
        $objWriter->writeAttribute('eqn', 'sum @10 21600 0');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('v:path');
        $objWriter->writeAttribute('o:extrusionok', 'f');
        $objWriter->writeAttribute('gradientshapeok', 't');
        $objWriter->writeAttribute('o:connecttype', 'rect');
        $objWriter->endElement();

        
        $objWriter->startElement('o:lock');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->writeAttribute('aspectratio', 't');
        $objWriter->endElement();

        $objWriter->endElement();

        
        foreach ($images as $key => $value) {
            $this->writeVMLHeaderFooterImage($objWriter, $key, $value);
        }

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeVMLHeaderFooterImage(XMLWriter $objWriter, string $reference, HeaderFooterDrawing $image): void
    {
        
        if (!Preg::isMatch('{(\d+)}', md5($reference), $m)) {
            
            throw new WriterException('Regexp failure in writeVMLHeaderFooterImage');
            
        }
        $id = 1500 + ((int) substr($m[1], 0, 2) * 1);

        
        $width = $image->getWidth();
        $height = $image->getHeight();
        $marginLeft = $image->getOffsetX();
        $marginTop = $image->getOffsetY();

        
        $objWriter->startElement('v:shape');
        $objWriter->writeAttribute('id', $reference);
        $objWriter->writeAttribute('o:spid', '_x0000_s' . $id);
        $objWriter->writeAttribute('type', '
        $objWriter->writeAttribute('style', "position:absolute;margin-left:{$marginLeft}px;margin-top:{$marginTop}px;width:{$width}px;height:{$height}px;z-index:1");

        
        $objWriter->startElement('v:imagedata');
        $objWriter->writeAttribute('o:relid', 'rId' . $reference);
        $objWriter->writeAttribute('o:title', $image->getName());
        $objWriter->endElement();

        
        $objWriter->startElement('o:lock');
        $objWriter->writeAttribute('v:ext', 'edit');
        $objWriter->writeAttribute('textRotation', 't');
        $objWriter->endElement();

        $objWriter->endElement();
    }

    
    public function allDrawings(Spreadsheet $spreadsheet): array
    {
        
        $aDrawings = [];

        
        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            
            $iterator = $spreadsheet->getSheet($i)->getDrawingCollection()->getIterator();
            while ($iterator->valid()) {
                $aDrawings[] = $iterator->current();

                $iterator->next();
            }
        }

        return $aDrawings;
    }

    private function writeHyperLinkDrawing(XMLWriter $objWriter, ?int $hlinkClickId): void
    {
        if ($hlinkClickId === null) {
            return;
        }

        $objWriter->startElement('a:hlinkClick');
        $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);
        $objWriter->writeAttribute('r:id', 'rId' . $hlinkClickId);
        $objWriter->endElement();
    }

    private static function stringEmu(int $pixelValue): string
    {
        return (string) SharedDrawing::pixelsToEMU($pixelValue);
    }

    private static function writeAttributeIf(XMLWriter $objWriter, ?bool $condition, string $attr, string $val): void
    {
        if ($condition) {
            $objWriter->writeAttribute($attr, $val);
        }
    }
}
