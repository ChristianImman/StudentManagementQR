<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class Biff8 extends Xls
{
    
    protected static function readBIFF8ConstantArray(string $arrayData): array
    {
        
        $nc = ord($arrayData[0]);

        
        $nr = self::getUInt2d($arrayData, 1);
        $size = 3; 
        $arrayData = substr($arrayData, 3);

        
        $matrixChunks = [];
        for ($r = 1; $r <= $nr + 1; ++$r) {
            $items = [];
            for ($c = 1; $c <= $nc + 1; ++$c) {
                $constant = self::readBIFF8Constant($arrayData);
                $items[] = $constant['value'];
                $arrayData = substr($arrayData, $constant['size']);
                $size += $constant['size'];
            }
            $matrixChunks[] = implode(',', $items); 
        }
        $matrix = '{' . implode(';', $matrixChunks) . '}';

        return [
            'value' => $matrix,
            'size' => $size,
        ];
    }

    
    private static function readBIFF8Constant(string $valueData): array
    {
        
        $identifier = ord($valueData[0]);

        switch ($identifier) {
            case 0x00: 
                $value = '';
                $size = 9;

                break;
            case 0x01: 
                
                $value = self::extractNumber(substr($valueData, 1, 8));
                $size = 9;

                break;
            case 0x02: 
                
                $string = self::readUnicodeStringLong(substr($valueData, 1));
                $value = '"' . $string['value'] . '"';
                $size = 1 + $string['size'];

                break;
            case 0x04: 
                
                if (ord($valueData[1])) {
                    $value = 'TRUE';
                } else {
                    $value = 'FALSE';
                }
                $size = 9;

                break;
            case 0x10: 
                
                $value = ErrorCode::lookup(ord($valueData[1]));
                $size = 9;

                break;
            default:
                throw new ReaderException('Unsupported BIFF8 constant');
        }

        return [
            'value' => $value,
            'size' => $size,
        ];
    }

    
    public static function readBIFF8CellRangeAddressList(string $subData): array
    {
        $cellRangeAddresses = [];

        
        $nm = self::getUInt2d($subData, 0);

        $offset = 2;
        
        for ($i = 0; $i < $nm; ++$i) {
            $cellRangeAddresses[] = self::readBIFF8CellRangeAddressFixed(substr($subData, $offset, 8));
            $offset += 8;
        }

        return [
            'size' => 2 + 8 * $nm,
            'cellRangeAddresses' => $cellRangeAddresses,
        ];
    }

    
    protected static function readBIFF8CellAddress(string $cellAddressStructure): string
    {
        
        $row = self::getUInt2d($cellAddressStructure, 0) + 1;

        
        
        $column = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($cellAddressStructure, 2)) + 1);

        
        if (!(0x4000 & self::getUInt2d($cellAddressStructure, 2))) {
            $column = '$' . $column;
        }
        
        if (!(0x8000 & self::getUInt2d($cellAddressStructure, 2))) {
            $row = '$' . $row;
        }

        return $column . $row;
    }

    
    protected static function readBIFF8CellAddressB(string $cellAddressStructure, string $baseCell = 'A1'): string
    {
        [$baseCol, $baseRow] = Coordinate::coordinateFromString($baseCell);
        $baseCol = Coordinate::columnIndexFromString($baseCol) - 1;
        $baseRow = (int) $baseRow;

        
        $rowIndex = self::getUInt2d($cellAddressStructure, 0);
        $row = self::getUInt2d($cellAddressStructure, 0) + 1;

        
        if (!(0x4000 & self::getUInt2d($cellAddressStructure, 2))) {
            
            
            $colIndex = 0x00FF & self::getUInt2d($cellAddressStructure, 2);

            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $column = '$' . $column;
        } else {
            
            
            $relativeColIndex = 0x00FF & self::getInt2d($cellAddressStructure, 2);
            $colIndex = $baseCol + $relativeColIndex;
            $colIndex = ($colIndex < 256) ? $colIndex : $colIndex - 256;
            $colIndex = ($colIndex >= 0) ? $colIndex : $colIndex + 256;
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
        }

        
        if (!(0x8000 & self::getUInt2d($cellAddressStructure, 2))) {
            $row = '$' . $row;
        } else {
            $rowIndex = ($rowIndex <= 32767) ? $rowIndex : $rowIndex - 65536;
            $row = $baseRow + $rowIndex;
        }

        return $column . $row;
    }

    
    protected static function readBIFF8CellRangeAddressFixed(string $subData): string
    {
        
        $fr = self::getUInt2d($subData, 0) + 1;

        
        $lr = self::getUInt2d($subData, 2) + 1;

        
        $fc = self::getUInt2d($subData, 4);

        
        $lc = self::getUInt2d($subData, 6);

        
        if ($fr > $lr || $fc > $lc) {
            throw new ReaderException('Not a cell range address');
        }

        
        $fc = Coordinate::stringFromColumnIndex($fc + 1);
        $lc = Coordinate::stringFromColumnIndex($lc + 1);

        if ($fr == $lr && $fc == $lc) {
            return "$fc$fr";
        }

        return "$fc$fr:$lc$lr";
    }

    
    protected static function readBIFF8CellRangeAddress(string $subData): string
    {
        
        

        
        $fr = self::getUInt2d($subData, 0) + 1;

        
        $lr = self::getUInt2d($subData, 2) + 1;

        

        
        $fc = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($subData, 4)) + 1);

        
        if (!(0x4000 & self::getUInt2d($subData, 4))) {
            $fc = '$' . $fc;
        }

        
        if (!(0x8000 & self::getUInt2d($subData, 4))) {
            $fr = '$' . $fr;
        }

        

        
        $lc = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($subData, 6)) + 1);

        
        if (!(0x4000 & self::getUInt2d($subData, 6))) {
            $lc = '$' . $lc;
        }

        
        if (!(0x8000 & self::getUInt2d($subData, 6))) {
            $lr = '$' . $lr;
        }

        return "$fc$fr:$lc$lr";
    }

    
    protected static function readBIFF8CellRangeAddressB(string $subData, string $baseCell = 'A1'): string
    {
        [$baseCol, $baseRow] = Coordinate::indexesFromString($baseCell);
        $baseCol = $baseCol - 1;

        
        

        
        $frIndex = self::getUInt2d($subData, 0); 

        
        $lrIndex = self::getUInt2d($subData, 2); 

        
        if (!(0x4000 & self::getUInt2d($subData, 4))) {
            
            
            
            $fcIndex = 0x00FF & self::getUInt2d($subData, 4);
            $fc = Coordinate::stringFromColumnIndex($fcIndex + 1);
            $fc = '$' . $fc;
        } else {
            
            
            
            $relativeFcIndex = 0x00FF & self::getInt2d($subData, 4);
            $fcIndex = $baseCol + $relativeFcIndex;
            $fcIndex = ($fcIndex < 256) ? $fcIndex : $fcIndex - 256;
            $fcIndex = ($fcIndex >= 0) ? $fcIndex : $fcIndex + 256;
            $fc = Coordinate::stringFromColumnIndex($fcIndex + 1);
        }

        
        if (!(0x8000 & self::getUInt2d($subData, 4))) {
            
            $fr = $frIndex + 1;
            $fr = '$' . $fr;
        } else {
            
            $frIndex = ($frIndex <= 32767) ? $frIndex : $frIndex - 65536;
            $fr = $baseRow + $frIndex;
        }

        
        if (!(0x4000 & self::getUInt2d($subData, 6))) {
            
            
            
            $lcIndex = 0x00FF & self::getUInt2d($subData, 6);
            $lc = Coordinate::stringFromColumnIndex($lcIndex + 1);
            $lc = '$' . $lc;
        } else {
            
            
            
            $relativeLcIndex = 0x00FF & self::getInt2d($subData, 6);
            $lcIndex = $baseCol + $relativeLcIndex;
            $lcIndex = ($lcIndex < 256) ? $lcIndex : $lcIndex - 256;
            $lcIndex = ($lcIndex >= 0) ? $lcIndex : $lcIndex + 256;
            $lc = Coordinate::stringFromColumnIndex($lcIndex + 1);
        }

        
        if (!(0x8000 & self::getUInt2d($subData, 6))) {
            
            $lr = $lrIndex + 1;
            $lr = '$' . $lr;
        } else {
            
            $lrIndex = ($lrIndex <= 32767) ? $lrIndex : $lrIndex - 65536;
            $lr = $baseRow + $lrIndex;
        }

        return "$fc$fr:$lc$lr";
    }
}
