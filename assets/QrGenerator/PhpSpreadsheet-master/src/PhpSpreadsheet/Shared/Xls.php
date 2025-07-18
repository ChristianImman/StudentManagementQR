<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Xls
{
    
    public static function sizeCol(Worksheet $worksheet, string $col = 'A'): int
    {
        
        $font = $worksheet->getParentOrThrow()->getDefaultStyle()->getFont();

        $columnDimensions = $worksheet->getColumnDimensions();

        
        if (isset($columnDimensions[$col]) && $columnDimensions[$col]->getWidth() != -1) {
            
            $columnDimension = $columnDimensions[$col];
            $width = $columnDimension->getWidth();
            $pixelWidth = Drawing::cellDimensionToPixels($width, $font);
        } elseif ($worksheet->getDefaultColumnDimension()->getWidth() != -1) {
            
            $defaultColumnDimension = $worksheet->getDefaultColumnDimension();
            $width = $defaultColumnDimension->getWidth();
            $pixelWidth = Drawing::cellDimensionToPixels($width, $font);
        } else {
            
            $pixelWidth = Font::getDefaultColumnWidthByFont($font, true);
        }

        
        if (isset($columnDimensions[$col]) && !$columnDimensions[$col]->getVisible()) {
            $effectivePixelWidth = 0;
        } else {
            $effectivePixelWidth = $pixelWidth;
        }

        return $effectivePixelWidth;
    }

    
    public static function sizeRow(Worksheet $worksheet, int $row = 1): int
    {
        
        $font = $worksheet->getParentOrThrow()->getDefaultStyle()->getFont();

        $rowDimensions = $worksheet->getRowDimensions();

        
        if (isset($rowDimensions[$row]) && $rowDimensions[$row]->getRowHeight() != -1) {
            
            $rowDimension = $rowDimensions[$row];
            $rowHeight = $rowDimension->getRowHeight();
            $pixelRowHeight = (int) ceil(4 * $rowHeight / 3); 
        } elseif ($worksheet->getDefaultRowDimension()->getRowHeight() != -1) {
            
            $defaultRowDimension = $worksheet->getDefaultRowDimension();
            $pixelRowHeight = $defaultRowDimension->getRowHeight(Dimension::UOM_PIXELS);
        } else {
            
            $pointRowHeight = Font::getDefaultRowHeightByFont($font);
            $pixelRowHeight = Font::fontSizeToPixels((int) $pointRowHeight);
        }

        
        if (isset($rowDimensions[$row]) && !$rowDimensions[$row]->getVisible()) {
            $effectivePixelRowHeight = 0;
        } else {
            $effectivePixelRowHeight = $pixelRowHeight;
        }

        return (int) $effectivePixelRowHeight;
    }

    
    public static function getDistanceX(Worksheet $worksheet, string $startColumn = 'A', float|int $startOffsetX = 0, string $endColumn = 'A', float|int $endOffsetX = 0): int
    {
        $distanceX = 0;

        
        $startColumnIndex = Coordinate::columnIndexFromString($startColumn);
        $endColumnIndex = Coordinate::columnIndexFromString($endColumn);
        for ($i = $startColumnIndex; $i <= $endColumnIndex; ++$i) {
            $distanceX += self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($i));
        }

        
        $distanceX -= (int) floor(self::sizeCol($worksheet, $startColumn) * $startOffsetX / 1024);

        
        $distanceX -= (int) floor(self::sizeCol($worksheet, $endColumn) * (1 - $endOffsetX / 1024));

        return $distanceX;
    }

    
    public static function getDistanceY(Worksheet $worksheet, int $startRow = 1, float|int $startOffsetY = 0, int $endRow = 1, float|int $endOffsetY = 0): int
    {
        $distanceY = 0;

        
        for ($row = $startRow; $row <= $endRow; ++$row) {
            $distanceY += self::sizeRow($worksheet, $row);
        }

        
        $distanceY -= (int) floor(self::sizeRow($worksheet, $startRow) * $startOffsetY / 256);

        
        $distanceY -= (int) floor(self::sizeRow($worksheet, $endRow) * (1 - $endOffsetY / 256));

        return $distanceY;
    }

    
    public static function oneAnchor2twoAnchor(Worksheet $worksheet, string $coordinates, int $offsetX, int $offsetY, int $width, int $height): ?array
    {
        [$col_start, $row] = Coordinate::indexesFromString($coordinates);
        $row_start = $row - 1;

        $x1 = $offsetX;
        $y1 = $offsetY;

        
        $col_end = $col_start; 
        $row_end = $row_start; 

        
        if ($x1 >= self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_start))) {
            $x1 = 0;
        }
        if ($y1 >= self::sizeRow($worksheet, $row_start + 1)) {
            $y1 = 0;
        }

        $width = $width + $x1 - 1;
        $height = $height + $y1 - 1;

        
        while ($width >= self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_end))) {
            $width -= self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_end));
            ++$col_end;
        }

        
        while ($height >= self::sizeRow($worksheet, $row_end + 1)) {
            $height -= self::sizeRow($worksheet, $row_end + 1);
            ++$row_end;
        }

        
        
        if (self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_start)) == 0) {
            return null;
        }
        if (self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_end)) == 0) {
            return null;
        }
        if (self::sizeRow($worksheet, $row_start + 1) == 0) {
            return null;
        }
        if (self::sizeRow($worksheet, $row_end + 1) == 0) {
            return null;
        }

        
        $x1 = $x1 / self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_start)) * 1024;
        $y1 = $y1 / self::sizeRow($worksheet, $row_start + 1) * 256;
        $x2 = ($width + 1) / self::sizeCol($worksheet, Coordinate::stringFromColumnIndex($col_end)) * 1024; 
        $y2 = ($height + 1) / self::sizeRow($worksheet, $row_end + 1) * 256; 

        $startCoordinates = Coordinate::stringFromColumnIndex($col_start) . ($row_start + 1);
        $endCoordinates = Coordinate::stringFromColumnIndex($col_end) . ($row_end + 1);

        return [
            'startCoordinates' => $startCoordinates,
            'startOffsetX' => $x1,
            'startOffsetY' => $y1,
            'endCoordinates' => $endCoordinates,
            'endOffsetX' => $x2,
            'endOffsetY' => $y2,
        ];
    }
}
