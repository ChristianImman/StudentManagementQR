<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Theme as SpreadsheetTheme;

class Theme extends WriterPart
{
    
    public function writeTheme(Spreadsheet $spreadsheet): string
    {
        
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }
        $theme = $spreadsheet->getTheme();

        
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        
        $objWriter->startElement('a:theme');
        $objWriter->writeAttribute('xmlns:a', Namespaces::DRAWINGML);
        $objWriter->writeAttribute('name', 'Office Theme');

        
        $objWriter->startElement('a:themeElements');

        
        $objWriter->startElement('a:clrScheme');
        $objWriter->writeAttribute('name', $theme->getThemeColorName());

        $this->writeColourScheme($objWriter, $theme);

        $objWriter->endElement();

        
        $objWriter->startElement('a:fontScheme');
        $objWriter->writeAttribute('name', $theme->getThemeFontName());

        
        $objWriter->startElement('a:majorFont');
        $this->writeFonts(
            $objWriter,
            $theme->getMajorFontLatin(),
            $theme->getMajorFontEastAsian(),
            $theme->getMajorFontComplexScript(),
            $theme->getMajorFontSubstitutions()
        );
        $objWriter->endElement(); 

        
        $objWriter->startElement('a:minorFont');
        $this->writeFonts(
            $objWriter,
            $theme->getMinorFontLatin(),
            $theme->getMinorFontEastAsian(),
            $theme->getMinorFontComplexScript(),
            $theme->getMinorFontSubstitutions()
        );
        $objWriter->endElement(); 

        $objWriter->endElement(); 

        
        $objWriter->startElement('a:fmtScheme');
        $objWriter->writeAttribute('name', 'Office');

        
        $objWriter->startElement('a:fillStyleLst');

        
        $objWriter->startElement('a:solidFill');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gradFill');
        $objWriter->writeAttribute('rotWithShape', '1');

        
        $objWriter->startElement('a:gsLst');

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '0');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '50000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '300000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '35000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '37000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '300000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '100000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '15000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '350000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:lin');
        $objWriter->writeAttribute('ang', '16200000');
        $objWriter->writeAttribute('scaled', '1');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gradFill');
        $objWriter->writeAttribute('rotWithShape', '1');

        
        $objWriter->startElement('a:gsLst');

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '0');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '51000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '130000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '80000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '93000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '130000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '100000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '94000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '135000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:lin');
        $objWriter->writeAttribute('ang', '16200000');
        $objWriter->writeAttribute('scaled', '0');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:lnStyleLst');

        
        $objWriter->startElement('a:ln');
        $objWriter->writeAttribute('w', '9525');
        $objWriter->writeAttribute('cap', 'flat');
        $objWriter->writeAttribute('cmpd', 'sng');
        $objWriter->writeAttribute('algn', 'ctr');

        
        $objWriter->startElement('a:solidFill');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '95000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '105000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:prstDash');
        $objWriter->writeAttribute('val', 'solid');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:ln');
        $objWriter->writeAttribute('w', '25400');
        $objWriter->writeAttribute('cap', 'flat');
        $objWriter->writeAttribute('cmpd', 'sng');
        $objWriter->writeAttribute('algn', 'ctr');

        
        $objWriter->startElement('a:solidFill');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:prstDash');
        $objWriter->writeAttribute('val', 'solid');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:ln');
        $objWriter->writeAttribute('w', '38100');
        $objWriter->writeAttribute('cap', 'flat');
        $objWriter->writeAttribute('cmpd', 'sng');
        $objWriter->writeAttribute('algn', 'ctr');

        
        $objWriter->startElement('a:solidFill');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:prstDash');
        $objWriter->writeAttribute('val', 'solid');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:effectStyleLst');

        
        $objWriter->startElement('a:effectStyle');

        
        $objWriter->startElement('a:effectLst');

        
        $objWriter->startElement('a:outerShdw');
        $objWriter->writeAttribute('blurRad', '40000');
        $objWriter->writeAttribute('dist', '20000');
        $objWriter->writeAttribute('dir', '5400000');
        $objWriter->writeAttribute('rotWithShape', '0');

        
        $objWriter->startElement('a:srgbClr');
        $objWriter->writeAttribute('val', '000000');

        
        $objWriter->startElement('a:alpha');
        $objWriter->writeAttribute('val', '38000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:effectStyle');

        
        $objWriter->startElement('a:effectLst');

        
        $objWriter->startElement('a:outerShdw');
        $objWriter->writeAttribute('blurRad', '40000');
        $objWriter->writeAttribute('dist', '23000');
        $objWriter->writeAttribute('dir', '5400000');
        $objWriter->writeAttribute('rotWithShape', '0');

        
        $objWriter->startElement('a:srgbClr');
        $objWriter->writeAttribute('val', '000000');

        
        $objWriter->startElement('a:alpha');
        $objWriter->writeAttribute('val', '35000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:effectStyle');

        
        $objWriter->startElement('a:effectLst');

        
        $objWriter->startElement('a:outerShdw');
        $objWriter->writeAttribute('blurRad', '40000');
        $objWriter->writeAttribute('dist', '23000');
        $objWriter->writeAttribute('dir', '5400000');
        $objWriter->writeAttribute('rotWithShape', '0');

        
        $objWriter->startElement('a:srgbClr');
        $objWriter->writeAttribute('val', '000000');

        
        $objWriter->startElement('a:alpha');
        $objWriter->writeAttribute('val', '35000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:scene3d');

        
        $objWriter->startElement('a:camera');
        $objWriter->writeAttribute('prst', 'orthographicFront');

        
        $objWriter->startElement('a:rot');
        $objWriter->writeAttribute('lat', '0');
        $objWriter->writeAttribute('lon', '0');
        $objWriter->writeAttribute('rev', '0');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:lightRig');
        $objWriter->writeAttribute('rig', 'threePt');
        $objWriter->writeAttribute('dir', 't');

        
        $objWriter->startElement('a:rot');
        $objWriter->writeAttribute('lat', '0');
        $objWriter->writeAttribute('lon', '0');
        $objWriter->writeAttribute('rev', '1200000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:sp3d');

        
        $objWriter->startElement('a:bevelT');
        $objWriter->writeAttribute('w', '63500');
        $objWriter->writeAttribute('h', '25400');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:bgFillStyleLst');

        
        $objWriter->startElement('a:solidFill');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');
        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gradFill');
        $objWriter->writeAttribute('rotWithShape', '1');

        
        $objWriter->startElement('a:gsLst');

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '0');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '40000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '350000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '40000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '45000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '99000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '350000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '100000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '20000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '255000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:path');
        $objWriter->writeAttribute('path', 'circle');

        
        $objWriter->startElement('a:fillToRect');
        $objWriter->writeAttribute('l', '50000');
        $objWriter->writeAttribute('t', '-80000');
        $objWriter->writeAttribute('r', '50000');
        $objWriter->writeAttribute('b', '180000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gradFill');
        $objWriter->writeAttribute('rotWithShape', '1');

        
        $objWriter->startElement('a:gsLst');

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '0');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:tint');
        $objWriter->writeAttribute('val', '80000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '300000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:gs');
        $objWriter->writeAttribute('pos', '100000');

        
        $objWriter->startElement('a:schemeClr');
        $objWriter->writeAttribute('val', 'phClr');

        
        $objWriter->startElement('a:shade');
        $objWriter->writeAttribute('val', '30000');
        $objWriter->endElement();

        
        $objWriter->startElement('a:satMod');
        $objWriter->writeAttribute('val', '200000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->startElement('a:path');
        $objWriter->writeAttribute('path', 'circle');

        
        $objWriter->startElement('a:fillToRect');
        $objWriter->writeAttribute('l', '50000');
        $objWriter->writeAttribute('t', '50000');
        $objWriter->writeAttribute('r', '50000');
        $objWriter->writeAttribute('b', '50000');
        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        $objWriter->endElement();

        
        $objWriter->writeElement('a:objectDefaults', null);

        
        $objWriter->writeElement('a:extraClrSchemeLst', null);

        $objWriter->endElement();

        
        return $objWriter->getData();
    }

    
    private function writeFonts(XMLWriter $objWriter, string $latinFont, string $eastAsianFont, string $complexScriptFont, array $fontSet): void
    {
        
        $objWriter->startElement('a:latin');
        $objWriter->writeAttribute('typeface', $latinFont);
        $objWriter->endElement();

        
        $objWriter->startElement('a:ea');
        $objWriter->writeAttribute('typeface', $eastAsianFont);
        $objWriter->endElement();

        
        $objWriter->startElement('a:cs');
        $objWriter->writeAttribute('typeface', $complexScriptFont);
        $objWriter->endElement();

        foreach ($fontSet as $fontScript => $typeface) {
            $objWriter->startElement('a:font');
            $objWriter->writeAttribute('script', $fontScript);
            $objWriter->writeAttribute('typeface', $typeface);
            $objWriter->endElement();
        }
    }

    
    private function writeColourScheme(XMLWriter $objWriter, SpreadsheetTheme $theme): void
    {
        $themeArray = $theme->getThemeColors();
        
        $objWriter->startElement('a:dk1');
        $objWriter->startElement('a:sysClr');
        $objWriter->writeAttribute('val', 'windowText');
        $objWriter->writeAttribute('lastClr', $themeArray['dk1'] ?? '000000');
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        
        $objWriter->startElement('a:lt1');
        $objWriter->startElement('a:sysClr');
        $objWriter->writeAttribute('val', 'window');
        $objWriter->writeAttribute('lastClr', $themeArray['lt1'] ?? 'FFFFFF');
        $objWriter->endElement(); 
        $objWriter->endElement(); 

        foreach ($themeArray as $colourName => $colourValue) {
            if ($colourName !== 'dk1' && $colourName !== 'lt1') {
                $objWriter->startElement('a:' . $colourName);
                $objWriter->startElement('a:srgbClr');
                $objWriter->writeAttribute('val', $colourValue);
                $objWriter->endElement(); 
                $objWriter->endElement(); 
            }
        }
    }
}
