<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Functions
{
    const PRECISION = 8.88E-016;

    
    const M_2DIVPI = 0.63661977236758134307553505349006;

    const COMPATIBILITY_EXCEL = 'Excel';
    const COMPATIBILITY_GNUMERIC = 'Gnumeric';
    const COMPATIBILITY_OPENOFFICE = 'OpenOfficeCalc';

    
    const RETURNDATE_PHP_NUMERIC = 'P';
    
    const RETURNDATE_UNIX_TIMESTAMP = 'P';
    const RETURNDATE_PHP_OBJECT = 'O';
    const RETURNDATE_PHP_DATETIME_OBJECT = 'O';
    const RETURNDATE_EXCEL = 'E';

    public const NOT_YET_IMPLEMENTED = '

    
    protected static string $compatibilityMode = self::COMPATIBILITY_EXCEL;

    
    protected static string $returnDateType = self::RETURNDATE_EXCEL;

    
    public static function setCompatibilityMode(string $compatibilityMode): bool
    {
        if (
            ($compatibilityMode == self::COMPATIBILITY_EXCEL)
            || ($compatibilityMode == self::COMPATIBILITY_GNUMERIC)
            || ($compatibilityMode == self::COMPATIBILITY_OPENOFFICE)
        ) {
            self::$compatibilityMode = $compatibilityMode;

            return true;
        }

        return false;
    }

    
    public static function getCompatibilityMode(): string
    {
        return self::$compatibilityMode;
    }

    
    public static function setReturnDateType(string $returnDateType): bool
    {
        if (
            ($returnDateType == self::RETURNDATE_UNIX_TIMESTAMP)
            || ($returnDateType == self::RETURNDATE_PHP_DATETIME_OBJECT)
            || ($returnDateType == self::RETURNDATE_EXCEL)
        ) {
            self::$returnDateType = $returnDateType;

            return true;
        }

        return false;
    }

    
    public static function getReturnDateType(): string
    {
        return self::$returnDateType;
    }

    
    public static function DUMMY(): string
    {
        return self::NOT_YET_IMPLEMENTED;
    }

    public static function isMatrixValue(mixed $idx): bool
    {
        $idx = StringHelper::convertToString($idx);

        return (substr_count($idx, '.') <= 1) || (preg_match('/\.[A-Z]/', $idx) > 0);
    }

    public static function isValue(mixed $idx): bool
    {
        $idx = StringHelper::convertToString($idx);

        return substr_count($idx, '.') === 0;
    }

    public static function isCellValue(mixed $idx): bool
    {
        $idx = StringHelper::convertToString($idx);

        return substr_count($idx, '.') > 1;
    }

    public static function ifCondition(mixed $condition): string
    {
        $condition = self::flattenSingleValue($condition);

        if ($condition === '' || $condition === null) {
            return '=""';
        }
        if (!is_string($condition) || !in_array($condition[0], ['>', '<', '='], true)) {
            $condition = self::operandSpecialHandling($condition);
            if (is_bool($condition)) {
                return '=' . ($condition ? 'TRUE' : 'FALSE');
            }
            if (!is_numeric($condition)) {
                if ($condition !== '""') { 
                    
                    $condition = (string) preg_replace('/"/ui', '""', $condition);
                }
                $condition = Calculation::wrapResult(strtoupper($condition));
            }

            return str_replace('""""', '""', '=' . StringHelper::convertToString($condition));
        }
        $operator = $operand = '';
        if (1 === preg_match('/(=|<[>=]?|>=?)(.*)/', $condition, $matches)) {
            [, $operator, $operand] = $matches;
        }

        $operand = (string) self::operandSpecialHandling($operand);
        if (is_numeric(trim($operand, '"'))) {
            $operand = trim($operand, '"');
        } elseif (!is_numeric($operand) && $operand !== 'FALSE' && $operand !== 'TRUE') {
            $operand = str_replace('"', '""', $operand);
            $operand = Calculation::wrapResult(strtoupper($operand));
            $operand = StringHelper::convertToString($operand);
        }

        return str_replace('""""', '""', $operator . $operand);
    }

    private static function operandSpecialHandling(mixed $operand): bool|float|int|string
    {
        if (is_numeric($operand) || is_bool($operand)) {
            return $operand;
        }
        $operand = StringHelper::convertToString($operand);
        if (strtoupper($operand) === Calculation::getTRUE() || strtoupper($operand) === Calculation::getFALSE()) {
            return strtoupper($operand);
        }

        
        if (preg_match('/^\-?\d*\.?\d*\s?\%$/', $operand)) {
            return ((float) rtrim($operand, '%')) / 100;
        }

        
        if (($dateValueOperand = Date::stringToExcel($operand)) !== false) {
            return $dateValueOperand;
        }

        return $operand;
    }

    
    public static function flattenArray(mixed $array): array
    {
        if (!is_array($array)) {
            return (array) $array;
        }

        $flattened = [];
        $stack = array_values($array);

        while (!empty($stack)) {
            $value = array_shift($stack);

            if (is_array($value)) {
                array_unshift($stack, ...array_values($value));
            } else {
                $flattened[] = $value;
            }
        }

        return $flattened;
    }

    
    public static function flattenArray2(mixed ...$array): array
    {
        $flattened = [];
        $stack = array_values($array);

        while (!empty($stack)) {
            $value = array_shift($stack);

            if (is_array($value)) {
                array_unshift($stack, ...array_values($value));
            } else {
                $flattened[] = $value;
            }
        }

        return $flattened;
    }

    public static function scalar(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        do {
            $value = array_pop($value);
        } while (is_array($value));

        return $value;
    }

    
    public static function flattenArrayIndexed($array): array
    {
        if (!is_array($array)) {
            return (array) $array;
        }

        $arrayValues = [];
        foreach ($array as $k1 => $value) {
            if (is_array($value)) {
                foreach ($value as $k2 => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k3 => $v) {
                            $arrayValues[$k1 . '.' . $k2 . '.' . $k3] = $v;
                        }
                    } else {
                        $arrayValues[$k1 . '.' . $k2] = $val;
                    }
                }
            } else {
                $arrayValues[$k1] = $value;
            }
        }

        return $arrayValues;
    }

    
    public static function flattenSingleValue(mixed $value): mixed
    {
        while (is_array($value)) {
            $value = array_shift($value);
        }

        return $value;
    }

    public static function expandDefinedName(string $coordinate, Cell $cell): string
    {
        $worksheet = $cell->getWorksheet();
        $spreadsheet = $worksheet->getParentOrThrow();
        
        $pCoordinatex = strtoupper($coordinate);
        
        $pCoordinatex = (string) preg_replace('/^=/', '', $pCoordinatex);
        $defined = $spreadsheet->getDefinedName($pCoordinatex, $worksheet);
        if ($defined !== null) {
            $worksheet2 = $defined->getWorkSheet();
            if (!$defined->isFormula() && $worksheet2 !== null) {
                $coordinate = "'" . $worksheet2->getTitle() . "'!"
                    . (string) preg_replace('/^=/', '', str_replace('$', '', $defined->getValue()));
            }
        }

        return $coordinate;
    }

    public static function trimTrailingRange(string $coordinate): string
    {
        return (string) preg_replace('/:[\w\$]+$/', '', $coordinate);
    }

    public static function trimSheetFromCellReference(string $coordinate): string
    {
        if (str_contains($coordinate, '!')) {
            $coordinate = substr($coordinate, strrpos($coordinate, '!') + 1);
        }

        return $coordinate;
    }
}
