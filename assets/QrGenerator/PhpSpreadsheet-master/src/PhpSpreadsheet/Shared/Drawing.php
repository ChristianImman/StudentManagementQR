<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use SimpleXMLElement;

class Drawing
{
    
    public static function pixelsToEMU(int $pixelValue): int|float
    {
        return $pixelValue * 9525;
    }

    
    public static function EMUToPixels($emuValue): int
    {
        $emuValue = (int) $emuValue;
        if ($emuValue != 0) {
            return (int) round($emuValue / 9525);
        }

        return 0;
    }

    
    public static function pixelsToCellDimension(int $pixelValue, \PhpOffice\PhpSpreadsheet\Style\Font $defaultFont): int|float
    {
        
        $name = $defaultFont->getName();
        $size = $defaultFont->getSize();

        if (isset(Font::DEFAULT_COLUMN_WIDTHS[$name][$size])) {
            
            return $pixelValue * Font::DEFAULT_COLUMN_WIDTHS[$name][$size]['width']
                / Font::DEFAULT_COLUMN_WIDTHS[$name][$size]['px'];
        }

        
        
        return $pixelValue * 11 * Font::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['width']
            / Font::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['px'] / $size;
    }

    
    public static function cellDimensionToPixels(float $cellWidth, \PhpOffice\PhpSpreadsheet\Style\Font $defaultFont): int
    {
        
        $name = $defaultFont->getName();
        $size = $defaultFont->getSize();

        if (isset(Font::DEFAULT_COLUMN_WIDTHS[$name][$size])) {
            
            $colWidth = $cellWidth * Font::DEFAULT_COLUMN_WIDTHS[$name][$size]['px']
                / Font::DEFAULT_COLUMN_WIDTHS[$name][$size]['width'];
        } else {
            
            
            $colWidth = $cellWidth * $size * Font::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['px']
                / Font::DEFAULT_COLUMN_WIDTHS['Calibri'][11]['width'] / 11;
        }

        
        $colWidth = (int) round($colWidth);

        return $colWidth;
    }

    
    public static function pixelsToPoints(int $pixelValue): float
    {
        return $pixelValue * 0.75;
    }

    
    public static function pointsToPixels($pointValue): int
    {
        if ($pointValue != 0) {
            return (int) ceil($pointValue / 0.75);
        }

        return 0;
    }

    
    public static function degreesToAngle(int $degrees): int
    {
        return (int) round($degrees * 60000);
    }

    
    public static function angleToDegrees($angle): int
    {
        $angle = (int) $angle;
        if ($angle != 0) {
            return (int) round($angle / 60000);
        }

        return 0;
    }
}
