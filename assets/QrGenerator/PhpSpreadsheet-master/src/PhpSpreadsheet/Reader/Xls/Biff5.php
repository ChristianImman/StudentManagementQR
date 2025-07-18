<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class Biff5 extends Xls
{
    
    public static function readBIFF5CellRangeAddressFixed(string $subData): string
    {
        
        $fr = self::getUInt2d($subData, 0) + 1;

        
        $lr = self::getUInt2d($subData, 2) + 1;

        
        $fc = ord($subData[4]);

        
        $lc = ord($subData[5]);

        
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

    
    public static function readBIFF5CellRangeAddressList(string $subData): array
    {
        $cellRangeAddresses = [];

        
        $nm = self::getUInt2d($subData, 0);

        $offset = 2;
        
        for ($i = 0; $i < $nm; ++$i) {
            $cellRangeAddresses[] = self::readBIFF5CellRangeAddressFixed(substr($subData, $offset, 6));
            $offset += 6;
        }

        return [
            'size' => 2 + 6 * $nm,
            'cellRangeAddresses' => $cellRangeAddresses,
        ];
    }
}
