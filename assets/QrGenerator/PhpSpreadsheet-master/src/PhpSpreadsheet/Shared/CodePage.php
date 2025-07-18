<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class CodePage
{
    public const DEFAULT_CODE_PAGE = 'CP1252';

    
    private static array $pageArray = [
        0 => 'CP1252', 
        367 => 'ASCII', 
        437 => 'CP437', 
        
        737 => 'CP737', 
        775 => 'CP775', 
        850 => 'CP850', 
        852 => 'CP852', 
        855 => 'CP855', 
        857 => 'CP857', 
        858 => 'CP858', 
        860 => 'CP860', 
        861 => 'CP861', 
        862 => 'CP862', 
        863 => 'CP863', 
        864 => 'CP864', 
        865 => 'CP865', 
        866 => 'CP866', 
        869 => 'CP869', 
        874 => 'CP874', 
        932 => 'CP932', 
        936 => 'CP936', 
        949 => 'CP949', 
        950 => 'CP950', 
        1200 => 'UTF-16LE', 
        1250 => 'CP1250', 
        1251 => 'CP1251', 
        1252 => 'CP1252', 
        1253 => 'CP1253', 
        1254 => 'CP1254', 
        1255 => 'CP1255', 
        1256 => 'CP1256', 
        1257 => 'CP1257', 
        1258 => 'CP1258', 
        1361 => 'CP1361', 
        10000 => 'MAC', 
        10001 => 'CP932', 
        10002 => 'CP950', 
        10003 => 'CP1361', 
        10004 => 'MACARABIC', 
        10005 => 'MACHEBREW', 
        10006 => 'MACGREEK', 
        10007 => 'MACCYRILLIC', 
        10008 => 'CP936', 
        10010 => 'MACROMANIA', 
        10017 => 'MACUKRAINE', 
        10021 => 'MACTHAI', 
        10029 => ['MACCENTRALEUROPE', 'MAC-CENTRALEUROPE'], 
        10079 => 'MACICELAND', 
        10081 => 'MACTURKISH', 
        10082 => 'MACCROATIAN', 
        21010 => 'UTF-16LE', 
        32768 => 'MAC', 
        
        65000 => 'UTF-7', 
        65001 => 'UTF-8', 
        99999 => ['unsupported'], 
    ];

    public static function validate(string $codePage): bool
    {
        return in_array($codePage, self::$pageArray, true);
    }

    
    public static function numberToName(int $codePage): string
    {
        if (array_key_exists($codePage, self::$pageArray)) {
            $value = self::$pageArray[$codePage];
            if (is_array($value)) {
                foreach ($value as $encoding) {
                    if (@iconv('UTF-8', $encoding, ' ') !== false) {
                        self::$pageArray[$codePage] = $encoding;

                        return $encoding;
                    }
                }

                throw new PhpSpreadsheetException("Code page $codePage not implemented on this system.");
            } else {
                return $value;
            }
        }
        if ($codePage == 720 || $codePage == 32769) {
            throw new PhpSpreadsheetException("Code page $codePage not supported."); 
        }

        throw new PhpSpreadsheetException('Unknown codepage: ' . $codePage);
    }

    public static function getEncodings(): array
    {
        return self::$pageArray;
    }
}
