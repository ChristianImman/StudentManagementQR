<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Logical;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Information\Value;

class Conditional
{
    use ArrayEnabled;

    
    public static function statementIf(mixed $condition = true, mixed $returnIfTrue = 0, mixed $returnIfFalse = false): mixed
    {
        $condition = ($condition === null) ? true : Functions::flattenSingleValue($condition);

        if (ErrorValue::isError($condition)) {
            return $condition;
        }

        $returnIfTrue = $returnIfTrue ?? 0;
        $returnIfFalse = $returnIfFalse ?? false;

        return ((bool) $condition) ? $returnIfTrue : $returnIfFalse;
    }

    
    public static function statementSwitch(mixed ...$arguments): mixed
    {
        $result = ExcelError::VALUE();

        if (count($arguments) > 0) {
            $targetValue = Functions::flattenSingleValue($arguments[0]);
            $argc = count($arguments) - 1;
            $switchCount = floor($argc / 2);
            $hasDefaultClause = $argc % 2 !== 0;
            $defaultClause = $argc % 2 === 0 ? null : $arguments[$argc];

            $switchSatisfied = false;
            if ($switchCount > 0) {
                for ($index = 0; $index < $switchCount; ++$index) {
                    if ($targetValue == Functions::flattenSingleValue($arguments[$index * 2 + 1])) {
                        $result = $arguments[$index * 2 + 2];
                        $switchSatisfied = true;

                        break;
                    }
                }
            }

            if ($switchSatisfied !== true) {
                $result = $hasDefaultClause ? $defaultClause : ExcelError::NA();
            }
        }

        return $result;
    }

    
    public static function IFERROR(mixed $testValue = '', mixed $errorpart = ''): mixed
    {
        if (is_array($testValue)) {
            return self::evaluateArrayArgumentsSubset([self::class, __FUNCTION__], 1, $testValue, $errorpart);
        }

        $errorpart = $errorpart ?? '';
        $testValue = $testValue ?? 0; 

        return self::statementIf(ErrorValue::isError($testValue), $errorpart, $testValue);
    }

    
    public static function IFNA(mixed $testValue = '', mixed $napart = ''): mixed
    {
        if (is_array($testValue)) {
            return self::evaluateArrayArgumentsSubset([self::class, __FUNCTION__], 1, $testValue, $napart);
        }

        $napart = $napart ?? '';
        $testValue = $testValue ?? 0; 

        return self::statementIf(ErrorValue::isNa($testValue), $napart, $testValue);
    }

    
    public static function IFS(mixed ...$arguments)
    {
        $argumentCount = count($arguments);

        if ($argumentCount % 2 != 0) {
            return ExcelError::NA();
        }
        
        $falseValueException = new Exception();
        for ($i = 0; $i < $argumentCount; $i += 2) {
            $testValue = ($arguments[$i] === null) ? '' : Functions::flattenSingleValue($arguments[$i]);
            $returnIfTrue = ($arguments[$i + 1] === null) ? '' : $arguments[$i + 1];
            $result = self::statementIf($testValue, $returnIfTrue, $falseValueException);

            if ($result !== $falseValueException) {
                return $result;
            }
        }

        return ExcelError::NA();
    }
}
