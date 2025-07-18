<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

class CalculationBase
{
    
    public static function getFunctions(): array
    {
        return FunctionArray::$phpSpreadsheetFunctions;
    }

    
    protected static function &getFunctionsAddress(): array
    {
        return FunctionArray::$phpSpreadsheetFunctions;
    }

    
    public static function addFunction(string $key, array $value): bool
    {
        $key = strtoupper($key);
        if (array_key_exists($key, FunctionArray::$phpSpreadsheetFunctions)) {
            return false;
        }
        $value['custom'] = true;
        FunctionArray::$phpSpreadsheetFunctions[$key] = $value;

        return true;
    }

    public static function removeFunction(string $key): bool
    {
        $key = strtoupper($key);
        if (array_key_exists($key, FunctionArray::$phpSpreadsheetFunctions)) {
            if (FunctionArray::$phpSpreadsheetFunctions[$key]['custom'] ?? false) {
                unset(FunctionArray::$phpSpreadsheetFunctions[$key]);

                return true;
            }
        }

        return false;
    }
}
