<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class Style extends WriterPart
{
    
    public function writeStyles(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('styleSheet');
        $objWriter->writeAttribute('xml:space', 'preserve');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);

        
        $objWriter->startElement('numFmts');
        $objWriter->writeAttribute('count', (string) $this->getParentWriter()->getNumFmtHashTable()->count());

        
        for ($i = 0; $i < $this->getParentWriter()->getNumFmtHashTable()->count(); ++$i) {
            $this->writeNumFmt($objWriter, $this->getParentWriter()->getNumFmtHashTable()->getByIndex($i), $i);
        }

        $objWriter->endElement();

        
        $objWriter->startElement('fonts');
        $objWriter->writeAttribute('count', (string) $this->getParentWriter()->getFontHashTable()->count());

        
        for ($i = 0; $i < $this->getParentWriter()->getFontHashTable()->count(); ++$i) {
            $thisfont = $this->getParentWriter()->getFontHashTable()->getByIndex($i);
            if ($thisfont !== null) {
                $this->writeFont($objWriter, $thisfont);
            }
        }

        $objWriter->endElement();

        
        $objWriter->startElement('fills');
        $objWriter->writeAttribute('count', (string) $this->getParentWriter()->getFillHashTable()->count());

        
        for ($i = 0; $i < $this->getParentWriter()->getFillHashTable()->count(); ++$i) {
            $thisfill = $this->getParentWriter()->getFillHashTable()->getByIndex($i);
            if ($thisfill !== null) {
                $this->writeFill($objWriter, $thisfill);
            }
        }

        $objWriter->endElement();

        
        $objWriter->startElement('borders');
        $objWriter->writeAttribute('count', (string) $this->getParentWriter()->getBordersHashTable()->count());

        
        for ($i = 0; $i < $this->getParentWriter()->getBordersHashTable()->count(); ++$i) {
            $thisborder = $this->getParentWriter()->getBordersHashTable()->getByIndex($i);
            if ($thisborder !== null) {
                $this->writeBorder($objWriter, $thisborder);
            }
        }

        $objWriter->endElement();

        
        $objWriter->startElement('cellStyleXfs');
        $objWriter->writeAttribute('count', '1');

        
        $objWriter->startElement('xf');
        $objWriter->writeAttribute('numFmtId', '0');
        $objWriter->writeAttribute('fontId', '0');
        $objWriter->writeAttribute('fillId', '0');
        $objWriter->writeAttribute('borderId', '0');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('cellXfs');
        $objWriter->writeAttribute('count', (string) count($spreadsheet->getCellXfCollection()));

        
        $alignment = new Alignment();
        $defaultAlignHash = $alignment->getHashCode();
        if ($defaultAlignHash !== $spreadsheet->getDefaultStyle()->getAlignment()->getHashCode()) {
            $defaultAlignHash = '';
        }
        foreach ($spreadsheet->getCellXfCollection() as $cellXf) {
            $this->writeCellStyleXf($objWriter, $cellXf, $spreadsheet, $defaultAlignHash);
        }

        $objWriter->endElement();

        
        $objWriter->startElement('cellStyles');
        $objWriter->writeAttribute('count', '1');

        
        $objWriter->startElement('cellStyle');
        $objWriter->writeAttribute('name', 'Normal');
        $objWriter->writeAttribute('xfId', '0');
        $objWriter->writeAttribute('builtinId', '0');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('dxfs');
        $objWriter->writeAttribute('count', (string) $this->getParentWriter()->getStylesConditionalHashTable()->count());

        
        for ($i = 0; $i < $this->getParentWriter()->getStylesConditionalHashTable()->count(); ++$i) {
            
            $thisstyle = $this->getParentWriter()->getStylesConditionalHashTable()->getByIndex($i);
            if ($thisstyle !== null) {
                $this->writeCellStyleDxf($objWriter, $thisstyle->getStyle());
            }
        }

        $objWriter->endElement();

        
        $objWriter->startElement('tableStyles');
        $objWriter->writeAttribute('defaultTableStyle', 'TableStyleMedium9');
        $objWriter->writeAttribute('defaultPivotStyle', 'PivotTableStyle1');
        $objWriter->endElement();

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeFill(XMLWriter $objWriter, Fill $fill): void
    {
        
        if (
            $fill->getFillType() === Fill::FILL_GRADIENT_LINEAR
            || $fill->getFillType() === Fill::FILL_GRADIENT_PATH
        ) {
            
            $this->writeGradientFill($objWriter, $fill);
        } elseif ($fill->getFillType() !== null) {
            
            $this->writePatternFill($objWriter, $fill);
        }
    }

    
    private function writeGradientFill(XMLWriter $objWriter, Fill $fill): void
    {
        
        $objWriter->startElement('fill');

        
        $objWriter->startElement('gradientFill');
        $objWriter->writeAttribute('type', (string) $fill->getFillType());
        $objWriter->writeAttribute('degree', (string) $fill->getRotation());

        
        $objWriter->startElement('stop');
        $objWriter->writeAttribute('position', '0');

        
        if (!empty($fill->getStartColor()->getARGB())) {
            $objWriter->startElement('color');
            $objWriter->writeAttribute('rgb', $fill->getStartColor()->getARGB());
            $objWriter->endElement();
        }

        $objWriter->endElement();

        
        $objWriter->startElement('stop');
        $objWriter->writeAttribute('position', '1');

        
        if (!empty($fill->getEndColor()->getARGB())) {
            $objWriter->startElement('color');
            $objWriter->writeAttribute('rgb', $fill->getEndColor()->getARGB());
            $objWriter->endElement();
        }

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();
    }

    private static function writePatternColors(Fill $fill): bool
    {
        if ($fill->getFillType() === Fill::FILL_NONE) {
            return false;
        }

        return $fill->getFillType() === Fill::FILL_SOLID || $fill->getColorsChanged();
    }

    
    private function writePatternFill(XMLWriter $objWriter, Fill $fill): void
    {
        
        $objWriter->startElement('fill');

        
        $objWriter->startElement('patternFill');
        if ($fill->getFillType()) {
            $objWriter->writeAttribute('patternType', (string) $fill->getFillType());
        }

        if (self::writePatternColors($fill)) {
            
            if ($fill->getStartColor()->getARGB()) {
                if (!$fill->getEndColor()->getARGB() && $fill->getFillType() === Fill::FILL_SOLID) {
                    $objWriter->startElement('bgColor');
                    $objWriter->writeAttribute('rgb', $fill->getStartColor()->getARGB());
                } else {
                    $objWriter->startElement('fgColor');
                    $objWriter->writeAttribute('rgb', $fill->getStartColor()->getARGB());
                }
                $objWriter->endElement();
            }
            
            if ($fill->getEndColor()->getARGB()) {
                $objWriter->startElement('bgColor');
                $objWriter->writeAttribute('rgb', $fill->getEndColor()->getARGB());
                $objWriter->endElement();
            }
        }

        $objWriter->endElement();

        $objWriter->endElement();
    }

    private function startFont(XMLWriter $objWriter, bool &$fontStarted): void
    {
        if (!$fontStarted) {
            $fontStarted = true;
            $objWriter->startElement('font');
        }
    }

    
    private function writeFont(XMLWriter $objWriter, Font $font): void
    {
        $fontStarted = false;
        
        
        
        

        
        
        
        if ($font->getBold() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('b');
            $objWriter->writeAttribute('val', $font->getBold() ? '1' : '0');
            $objWriter->endElement();
        }

        
        if ($font->getItalic() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('i');
            $objWriter->writeAttribute('val', $font->getItalic() ? '1' : '0');
            $objWriter->endElement();
        }

        
        if ($font->getStrikethrough() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('strike');
            $objWriter->writeAttribute('val', $font->getStrikethrough() ? '1' : '0');
            $objWriter->endElement();
        }

        
        if ($font->getUnderline() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('u');
            $objWriter->writeAttribute('val', $font->getUnderline());
            $objWriter->endElement();
        }

        
        if ($font->getSuperscript() === true || $font->getSubscript() === true) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('vertAlign');
            if ($font->getSuperscript() === true) {
                $objWriter->writeAttribute('val', 'superscript');
            } elseif ($font->getSubscript() === true) {
                $objWriter->writeAttribute('val', 'subscript');
            }
            $objWriter->endElement();
        }

        
        if ($font->getSize() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('sz');
            $objWriter->writeAttribute('val', StringHelper::formatNumber($font->getSize()));
            $objWriter->endElement();
        }

        
        if ($font->getColor()->getARGB() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('color');
            $objWriter->writeAttribute('rgb', $font->getColor()->getARGB());
            $objWriter->endElement();
        }

        
        if ($font->getName() !== null) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('name');
            $objWriter->writeAttribute('val', $font->getName());
            $objWriter->endElement();
        }

        if (!empty($font->getScheme())) {
            $this->startFont($objWriter, $fontStarted);
            $objWriter->startElement('scheme');
            $objWriter->writeAttribute('val', $font->getScheme());
            $objWriter->endElement();
        }

        if ($fontStarted) {
            $objWriter->endElement();
        }
    }

    
    private function writeBorder(XMLWriter $objWriter, Borders $borders): void
    {
        
        $objWriter->startElement('border');
        
        switch ($borders->getDiagonalDirection()) {
            case Borders::DIAGONAL_UP:
                $objWriter->writeAttribute('diagonalUp', 'true');
                $objWriter->writeAttribute('diagonalDown', 'false');

                break;
            case Borders::DIAGONAL_DOWN:
                $objWriter->writeAttribute('diagonalUp', 'false');
                $objWriter->writeAttribute('diagonalDown', 'true');

                break;
            case Borders::DIAGONAL_BOTH:
                $objWriter->writeAttribute('diagonalUp', 'true');
                $objWriter->writeAttribute('diagonalDown', 'true');

                break;
        }

        
        $this->writeBorderPr($objWriter, 'left', $borders->getLeft());
        $this->writeBorderPr($objWriter, 'right', $borders->getRight());
        $this->writeBorderPr($objWriter, 'top', $borders->getTop());
        $this->writeBorderPr($objWriter, 'bottom', $borders->getBottom());
        $this->writeBorderPr($objWriter, 'diagonal', $borders->getDiagonal());
        $objWriter->endElement();
    }

    
    private function writeCellStyleXf(XMLWriter $objWriter, \PhpOffice\PhpSpreadsheet\Style\Style $style, Spreadsheet $spreadsheet, string $defaultAlignHash): void
    {
        
        $objWriter->startElement('xf');
        $objWriter->writeAttribute('xfId', '0');
        $objWriter->writeAttribute('fontId', (string) (int) $this->getParentWriter()->getFontHashTable()->getIndexForHashCode($style->getFont()->getHashCode()));
        if ($style->getQuotePrefix()) {
            $objWriter->writeAttribute('quotePrefix', '1');
        }

        if ($style->getNumberFormat()->getBuiltInFormatCode() === false) {
            $objWriter->writeAttribute('numFmtId', (string) (int) ($this->getParentWriter()->getNumFmtHashTable()->getIndexForHashCode($style->getNumberFormat()->getHashCode()) + 164));
        } else {
            $objWriter->writeAttribute('numFmtId', (string) (int) $style->getNumberFormat()->getBuiltInFormatCode());
        }

        $objWriter->writeAttribute('fillId', (string) (int) $this->getParentWriter()->getFillHashTable()->getIndexForHashCode($style->getFill()->getHashCode()));
        $objWriter->writeAttribute('borderId', (string) (int) $this->getParentWriter()->getBordersHashTable()->getIndexForHashCode($style->getBorders()->getHashCode()));

        
        $objWriter->writeAttribute('applyFont', ($spreadsheet->getDefaultStyle()->getFont()->getHashCode() != $style->getFont()->getHashCode()) ? '1' : '0');
        $objWriter->writeAttribute('applyNumberFormat', ($spreadsheet->getDefaultStyle()->getNumberFormat()->getHashCode() != $style->getNumberFormat()->getHashCode()) ? '1' : '0');
        $objWriter->writeAttribute('applyFill', ($spreadsheet->getDefaultStyle()->getFill()->getHashCode() != $style->getFill()->getHashCode()) ? '1' : '0');
        $objWriter->writeAttribute('applyBorder', ($spreadsheet->getDefaultStyle()->getBorders()->getHashCode() != $style->getBorders()->getHashCode()) ? '1' : '0');
        if ($defaultAlignHash !== '' && $defaultAlignHash === $style->getAlignment()->getHashCode()) {
            $applyAlignment = '0';
        } else {
            $applyAlignment = '1';
        }
        $objWriter->writeAttribute('applyAlignment', $applyAlignment);
        if ($style->getProtection()->getLocked() != Protection::PROTECTION_INHERIT || $style->getProtection()->getHidden() != Protection::PROTECTION_INHERIT) {
            $objWriter->writeAttribute('applyProtection', 'true');
        }

        
        if ($applyAlignment === '1') {
            $objWriter->startElement('alignment');
            $vertical = Alignment::VERTICAL_ALIGNMENT_FOR_XLSX[$style->getAlignment()->getVertical()] ?? '';
            $horizontal = Alignment::HORIZONTAL_ALIGNMENT_FOR_XLSX[$style->getAlignment()->getHorizontal()] ?? '';
            if ($horizontal !== '') {
                $objWriter->writeAttribute('horizontal', $horizontal);
            }
            if ($vertical !== '') {
                $objWriter->writeAttribute('vertical', $vertical);
            }
            $justifyLastLine = $style->getAlignment()->getJustifyLastLine();
            if (is_bool($justifyLastLine)) {
                $objWriter->writeAttribute('justifyLastLine', (string) (int) $justifyLastLine);
            }

            if ($style->getAlignment()->getTextRotation() >= 0) {
                $textRotation = $style->getAlignment()->getTextRotation();
            } else {
                $textRotation = 90 - $style->getAlignment()->getTextRotation();
            }
            $objWriter->writeAttribute('textRotation', (string) $textRotation);

            $objWriter->writeAttribute('wrapText', ($style->getAlignment()->getWrapText() ? 'true' : 'false'));
            $objWriter->writeAttribute('shrinkToFit', ($style->getAlignment()->getShrinkToFit() ? 'true' : 'false'));

            if ($style->getAlignment()->getIndent() > 0) {
                $objWriter->writeAttribute('indent', (string) $style->getAlignment()->getIndent());
            }
            if ($style->getAlignment()->getReadOrder() > 0) {
                $objWriter->writeAttribute('readingOrder', (string) $style->getAlignment()->getReadOrder());
            }
            $objWriter->endElement();
        }

        
        if ($style->getProtection()->getLocked() != Protection::PROTECTION_INHERIT || $style->getProtection()->getHidden() != Protection::PROTECTION_INHERIT) {
            $objWriter->startElement('protection');
            if ($style->getProtection()->getLocked() != Protection::PROTECTION_INHERIT) {
                $objWriter->writeAttribute('locked', ($style->getProtection()->getLocked() == Protection::PROTECTION_PROTECTED ? 'true' : 'false'));
            }
            if ($style->getProtection()->getHidden() != Protection::PROTECTION_INHERIT) {
                $objWriter->writeAttribute('hidden', ($style->getProtection()->getHidden() == Protection::PROTECTION_PROTECTED ? 'true' : 'false'));
            }
            $objWriter->endElement();
        }

        $objWriter->endElement();
    }

    
    private function writeCellStyleDxf(XMLWriter $objWriter, \PhpOffice\PhpSpreadsheet\Style\Style $style): void
    {
        
        $objWriter->startElement('dxf');

        
        $this->writeFont($objWriter, $style->getFont());

        
        $this->writeNumFmt($objWriter, $style->getNumberFormat());

        
        $this->writeFill($objWriter, $style->getFill());

        
        $this->writeBorder($objWriter, $style->getBorders());

        $objWriter->endElement();
    }

    
    private function writeBorderPr(XMLWriter $objWriter, string $name, Border $border): void
    {
        
        if ($border->getBorderStyle() === Border::BORDER_OMIT) {
            return;
        }
        $objWriter->startElement($name);
        if ($border->getBorderStyle() !== Border::BORDER_NONE) {
            $objWriter->writeAttribute('style', $border->getBorderStyle());

            
            if ($border->getColor()->getARGB() !== null) {
                $objWriter->startElement('color');
                $objWriter->writeAttribute('rgb', $border->getColor()->getARGB());
                $objWriter->endElement();
            }
        }
        $objWriter->endElement();
    }

    
    private function writeNumFmt(XMLWriter $objWriter, ?NumberFormat $numberFormat, int $id = 0): void
    {
        
        $formatCode = ($numberFormat === null) ? null : $numberFormat->getFormatCode();

        
        if ($formatCode !== null) {
            $objWriter->startElement('numFmt');
            $objWriter->writeAttribute('numFmtId', (string) ($id + 164));
            $objWriter->writeAttribute('formatCode', $formatCode);
            $objWriter->endElement();
        }
    }

    
    public function allStyles(Spreadsheet $spreadsheet): array
    {
        return $spreadsheet->getCellXfCollection();
    }

    
    public function allConditionalStyles(Spreadsheet $spreadsheet): array
    {
        
        $aStyles = [];

        $sheetCount = $spreadsheet->getSheetCount();
        for ($i = 0; $i < $sheetCount; ++$i) {
            foreach ($spreadsheet->getSheet($i)->getConditionalStylesCollection() as $conditionalStyles) {
                foreach ($conditionalStyles as $conditionalStyle) {
                    $aStyles[] = $conditionalStyle;
                }
            }
        }

        return $aStyles;
    }

    
    public function allFills(Spreadsheet $spreadsheet): array
    {
        
        $aFills = [];

        
        $fill0 = new Fill();
        $fill0->setFillType(Fill::FILL_NONE);
        $aFills[] = $fill0;

        $fill1 = new Fill();
        $fill1->setFillType(Fill::FILL_PATTERN_GRAY125);
        $aFills[] = $fill1;
        
        $aStyles = $this->allStyles($spreadsheet);
        foreach ($aStyles as $style) {
            if (!isset($aFills[$style->getFill()->getHashCode()])) {
                $aFills[$style->getFill()->getHashCode()] = $style->getFill();
            }
        }

        return $aFills;
    }

    
    public function allFonts(Spreadsheet $spreadsheet): array
    {
        
        $aFonts = [];
        $aStyles = $this->allStyles($spreadsheet);

        foreach ($aStyles as $style) {
            if (!isset($aFonts[$style->getFont()->getHashCode()])) {
                $aFonts[$style->getFont()->getHashCode()] = $style->getFont();
            }
        }

        return $aFonts;
    }

    
    public function allBorders(Spreadsheet $spreadsheet): array
    {
        
        $aBorders = [];
        $aStyles = $this->allStyles($spreadsheet);

        foreach ($aStyles as $style) {
            if (!isset($aBorders[$style->getBorders()->getHashCode()])) {
                $aBorders[$style->getBorders()->getHashCode()] = $style->getBorders();
            }
        }

        return $aBorders;
    }

    
    public function allNumberFormats(Spreadsheet $spreadsheet): array
    {
        
        $aNumFmts = [];
        $aStyles = $this->allStyles($spreadsheet);

        foreach ($aStyles as $style) {
            if ($style->getNumberFormat()->getBuiltInFormatCode() === false && !isset($aNumFmts[$style->getNumberFormat()->getHashCode()])) {
                $aNumFmts[$style->getNumberFormat()->getHashCode()] = $style->getNumberFormat();
            }
        }

        return $aNumFmts;
    }
}
