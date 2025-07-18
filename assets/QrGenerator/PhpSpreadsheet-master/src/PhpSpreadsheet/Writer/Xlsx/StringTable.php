<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\ChartColor;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet as ActualWorksheet;

class StringTable extends WriterPart
{
    
    public function createStringTable(ActualWorksheet $worksheet, ?array $existingTable = null): array
    {
        
        
        $aStringTable = $existingTable ?? [];

        
        $aFlippedStringTable = $this->flipStringTable($aStringTable);

        
        foreach ($worksheet->getCellCollection()->getCoordinates() as $coordinate) {
            
            $cell = $worksheet->getCellCollection()->get($coordinate);
            $cellValue = $cell->getValue();
            if (
                !is_object($cellValue)
                && ($cellValue !== null)
                && $cellValue !== ''
                && ($cell->getDataType() == DataType::TYPE_STRING || $cell->getDataType() == DataType::TYPE_STRING2 || $cell->getDataType() == DataType::TYPE_NULL)
                && !isset($aFlippedStringTable[$cellValue])
            ) {
                $aStringTable[] = $cellValue;
                $aFlippedStringTable[$cellValue] = true;
            } elseif (
                $cellValue instanceof RichText
                && !isset($aFlippedStringTable[$cellValue->getHashCode()])
            ) {
                $aStringTable[] = $cellValue;
                $aFlippedStringTable[$cellValue->getHashCode()] = true;
            }
        }
        

        return $aStringTable;
    }

    
    public function writeStringTable(array $stringTable): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('sst');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);
        $objWriter->writeAttribute('uniqueCount', (string) count($stringTable));

        
        foreach ($stringTable as $textElement) {
            $objWriter->startElement('si');

            if (!($textElement instanceof RichText)) {
                $textToWrite = StringHelper::controlCharacterPHP2OOXML($textElement);
                $objWriter->startElement('t');
                if ($textToWrite !== trim($textToWrite)) {
                    $objWriter->writeAttribute('xml:space', 'preserve');
                }
                $objWriter->writeRawData($textToWrite);
                $objWriter->endElement();
            } else {
                $this->writeRichText($objWriter, $textElement);
            }

            $objWriter->endElement();
        }

        $objWriter->endElement();

        return $objWriter->getData();
    }

    
    public function writeRichText(XMLWriter $objWriter, RichText $richText, ?string $prefix = null): void
    {
        if ($prefix !== null) {
            $prefix .= ':';
        }

        
        $elements = $richText->getRichTextElements();
        foreach ($elements as $element) {
            
            $objWriter->startElement($prefix . 'r');

            
            if ($element instanceof Run && $element->getFont() !== null) {
                
                $objWriter->startElement($prefix . 'rPr');

                
                if ($element->getFont()->getName() !== null) {
                    $objWriter->startElement($prefix . 'rFont');
                    $objWriter->writeAttribute('val', $element->getFont()->getName());
                    $objWriter->endElement();
                }

                
                $objWriter->startElement($prefix . 'b');
                $objWriter->writeAttribute('val', ($element->getFont()->getBold() ? 'true' : 'false'));
                $objWriter->endElement();

                
                $objWriter->startElement($prefix . 'i');
                $objWriter->writeAttribute('val', ($element->getFont()->getItalic() ? 'true' : 'false'));
                $objWriter->endElement();

                
                if ($element->getFont()->getSuperscript() || $element->getFont()->getSubscript()) {
                    $objWriter->startElement($prefix . 'vertAlign');
                    if ($element->getFont()->getSuperscript()) {
                        $objWriter->writeAttribute('val', 'superscript');
                    } elseif ($element->getFont()->getSubscript()) {
                        $objWriter->writeAttribute('val', 'subscript');
                    }
                    $objWriter->endElement();
                }

                
                $objWriter->startElement($prefix . 'strike');
                $objWriter->writeAttribute('val', ($element->getFont()->getStrikethrough() ? 'true' : 'false'));
                $objWriter->endElement();

                
                if ($element->getFont()->getColor()->getARGB() !== null) {
                    $objWriter->startElement($prefix . 'color');
                    $objWriter->writeAttribute('rgb', $element->getFont()->getColor()->getARGB());
                    $objWriter->endElement();
                }

                
                if ($element->getFont()->getSize() !== null) {
                    $objWriter->startElement($prefix . 'sz');
                    $objWriter->writeAttribute('val', (string) $element->getFont()->getSize());
                    $objWriter->endElement();
                }

                
                if ($element->getFont()->getUnderline() !== null) {
                    $objWriter->startElement($prefix . 'u');
                    $objWriter->writeAttribute('val', $element->getFont()->getUnderline());
                    $objWriter->endElement();
                }

                $objWriter->endElement();
            }

            
            $objWriter->startElement($prefix . 't');
            $objWriter->writeAttribute('xml:space', 'preserve');
            $objWriter->writeRawData(StringHelper::controlCharacterPHP2OOXML($element->getText()));
            $objWriter->endElement();

            $objWriter->endElement();
        }
    }

    
    public function writeRichTextForCharts(XMLWriter $objWriter, $richText = null, string $prefix = ''): void
    {
        if (!($richText instanceof RichText)) {
            $textRun = $richText;
            $richText = new RichText();
            $run = $richText->createTextRun($textRun ?? '');
            $run->setFont(null);
        }

        if ($prefix !== '') {
            $prefix .= ':';
        }

        
        $elements = $richText->getRichTextElements();
        foreach ($elements as $element) {
            
            $objWriter->startElement($prefix . 'r');
            if ($element->getFont() !== null) {
                
                $objWriter->startElement($prefix . 'rPr');
                $fontSize = $element->getFont()->getSize();
                if (is_numeric($fontSize)) {
                    $fontSize *= (($fontSize < 100) ? 100 : 1);
                    $objWriter->writeAttribute('sz', (string) $fontSize);
                }

                
                $objWriter->writeAttribute('b', ($element->getFont()->getBold() ? '1' : '0'));
                
                $objWriter->writeAttribute('i', ($element->getFont()->getItalic() ? '1' : '0'));
                
                $underlineType = $element->getFont()->getUnderline();
                switch ($underlineType) {
                    case 'single':
                        $underlineType = 'sng';

                        break;
                    case 'double':
                        $underlineType = 'dbl';

                        break;
                }
                if ($underlineType !== null) {
                    $objWriter->writeAttribute('u', $underlineType);
                }
                
                $objWriter->writeAttribute('strike', ($element->getFont()->getStriketype() ?: 'noStrike'));
                
                if ($element->getFont()->getBaseLine()) {
                    $objWriter->writeAttribute('baseline', (string) $element->getFont()->getBaseLine());
                }

                
                $this->writeChartTextColor($objWriter, $element->getFont()->getChartColor(), $prefix);

                
                $this->writeChartTextColor($objWriter, $element->getFont()->getUnderlineColor(), $prefix, 'uFill');

                
                if ($element->getFont()->getLatin()) {
                    $objWriter->startElement($prefix . 'latin');
                    $objWriter->writeAttribute('typeface', $element->getFont()->getLatin());
                    $objWriter->endElement();
                }
                if ($element->getFont()->getEastAsian()) {
                    $objWriter->startElement($prefix . 'ea');
                    $objWriter->writeAttribute('typeface', $element->getFont()->getEastAsian());
                    $objWriter->endElement();
                }
                if ($element->getFont()->getComplexScript()) {
                    $objWriter->startElement($prefix . 'cs');
                    $objWriter->writeAttribute('typeface', $element->getFont()->getComplexScript());
                    $objWriter->endElement();
                }

                $objWriter->endElement();
            }

            
            $objWriter->startElement($prefix . 't');
            $objWriter->writeRawData(StringHelper::controlCharacterPHP2OOXML($element->getText()));
            $objWriter->endElement();

            $objWriter->endElement();
        }
    }

    private function writeChartTextColor(XMLWriter $objWriter, ?ChartColor $underlineColor, string $prefix, ?string $openTag = ''): void
    {
        if ($underlineColor !== null) {
            $type = $underlineColor->getType();
            $value = $underlineColor->getValue();
            if (!empty($type) && !empty($value)) {
                if ($openTag !== '') {
                    $objWriter->startElement($prefix . $openTag);
                }
                $objWriter->startElement($prefix . 'solidFill');
                $objWriter->startElement($prefix . $type);
                $objWriter->writeAttribute('val', $value);
                $alpha = $underlineColor->getAlpha();
                if (is_numeric($alpha)) {
                    $objWriter->startElement('a:alpha');
                    $objWriter->writeAttribute('val', ChartColor::alphaToXml((int) $alpha));
                    $objWriter->endElement();
                }
                $objWriter->endElement(); 
                $objWriter->endElement(); 
                if ($openTag !== '') {
                    $objWriter->endElement(); 
                }
            }
        }
    }

    
    public function flipStringTable(array $stringTable): array
    {
        
        $returnValue = [];

        
        foreach ($stringTable as $key => $value) {
            if (!$value instanceof RichText) {
                $returnValue[$value] = $key;
            } elseif ($value instanceof RichText) {
                $returnValue[$value->getHashCode()] = $key;
            }
        }

        return $returnValue;
    }
}
