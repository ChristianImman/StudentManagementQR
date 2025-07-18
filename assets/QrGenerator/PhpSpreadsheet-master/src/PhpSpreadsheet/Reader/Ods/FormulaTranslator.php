<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Ods;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class FormulaTranslator
{
    private static function replaceQuotedPeriod(string $value): string
    {
        $value2 = '';
        $quoted = false;
        foreach (mb_str_split($value, 1, 'UTF-8') as $char) {
            if ($char === "'") {
                $quoted = !$quoted;
            } elseif ($char === '.' && $quoted) {
                $char = "\u{fffe}";
            }
            $value2 .= $char;
        }

        return $value2;
    }

    public static function convertToExcelAddressValue(string $openOfficeAddress): string
    {
        
        
        
        $excelAddress = (string) preg_replace(
            [
                '/\$?([^\.]+)\.([^\.]+):\$?([^\.]+)\.([^\.]+)/miu',
                '/\$?([^\.]+)\.([^\.]+):\.([^\.]+)/miu', 
                '/\$?([^\.]+)\.([^\.]+)/miu', 
                '/\.([^\.]+):\.([^\.]+)/miu', 
                '/\.([^\.]+)/miu', 
                '/\x{FFFE}/miu', 
            ],
            [
                '$1!$2:$4',
                '$1!$2:$3',
                '$1!$2',
                '$1:$2',
                '$1',
                '.',
            ],
            self::replaceQuotedPeriod($openOfficeAddress)
        );

        return $excelAddress;
    }

    public static function convertToExcelFormulaValue(string $openOfficeFormula): string
    {
        $temp = explode(Calculation::FORMULA_STRING_QUOTE, $openOfficeFormula);
        $tKey = false;
        $inMatrixBracesLevel = 0;
        $inFunctionBracesLevel = 0;
        foreach ($temp as &$value) {
            
            
            
            $tKey = $tKey === false;
            if ($tKey) {
                $value = (string) preg_replace(
                    [
                        '/\[\$?([^\.]+)\.([^\.]+):\.([^\.]+)\]/miu', 
                        '/\[\$?([^\.]+)\.([^\.]+)\]/miu', 
                        '/\[\.([^\.]+):\.([^\.]+)\]/miu', 
                        '/\[\.([^\.]+)\]/miu', 
                        '/\x{FFFE}/miu', 
                    ],
                    [
                        '$1!$2:$3',
                        '$1!$2',
                        '$1:$2',
                        '$1',
                        '.',
                    ],
                    self::replaceQuotedPeriod($value)
                );
                
                $value = str_replace('$$', '', $value);

                
                $value = Calculation::translateSeparator(';', ',', $value, $inFunctionBracesLevel);

                
                $value = Calculation::translateSeparator(
                    ';',
                    ',',
                    $value,
                    $inMatrixBracesLevel,
                    Calculation::FORMULA_OPEN_MATRIX_BRACE,
                    Calculation::FORMULA_CLOSE_MATRIX_BRACE
                );
                $value = Calculation::translateSeparator(
                    '|',
                    ';',
                    $value,
                    $inMatrixBracesLevel,
                    Calculation::FORMULA_OPEN_MATRIX_BRACE,
                    Calculation::FORMULA_CLOSE_MATRIX_BRACE
                );

                $value = (string) preg_replace('/COM\.MICROSOFT\./ui', '', $value);
            }
        }

        
        $excelFormula = implode('"', $temp);

        return $excelFormula;
    }
}
