<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class Subtotal
{
    protected static function filterHiddenArgs(Cell $cellReference, array $args): array
    {
        return array_filter(
            $args,
            function ($index) use ($cellReference) {
                $explodeArray = explode('.', $index);
                $row = $explodeArray[1] ?? '';
                if (!is_numeric($row)) {
                    return true;
                }

                return $cellReference->getWorksheet()->getRowDimension((int) $row)->getVisible();
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    protected static function filterFormulaArgs(Cell $cellReference, array $args): array
    {
        return array_filter(
            $args,
            function ($index) use ($cellReference): bool {
                $explodeArray = explode('.', $index);
                $row = $explodeArray[1] ?? '';
                $column = $explodeArray[2] ?? '';
                $retVal = true;
                if ($cellReference->getWorksheet()->cellExists($column . $row)) {
                    
                    $isFormula = $cellReference->getWorksheet()->getCell($column . $row)->isFormula();
                    $cellFormula = !preg_match(
                        '/^=.*\b(SUBTOTAL|AGGREGATE)\s*\(/i',
                        $cellReference->getWorksheet()->getCell($column . $row)->getValueString()
                    );

                    $retVal = !$isFormula || $cellFormula;
                }

                return $retVal;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    
    private const CALL_FUNCTIONS = [
        1 => [Statistical\Averages::class, 'average'], 
        [Statistical\Counts::class, 'COUNT'], 
        [Statistical\Counts::class, 'COUNTA'], 
        [Statistical\Maximum::class, 'max'], 
        [Statistical\Minimum::class, 'min'], 
        [Operations::class, 'product'], 
        [Statistical\StandardDeviations::class, 'STDEV'], 
        [Statistical\StandardDeviations::class, 'STDEVP'], 
        [Sum::class, 'sumIgnoringStrings'], 
        [Statistical\Variances::class, 'VAR'], 
        [Statistical\Variances::class, 'VARP'], 
    ];

    
    public static function evaluate(mixed $functionType, ...$args): float|int|string
    {
        
        $cellReference = array_pop($args);
        $bArgs = Functions::flattenArrayIndexed($args);
        $aArgs = [];
        
        
        
        
        foreach ($bArgs as $key => $value) {
            if (is_int($key)) {
                $aArgs[$key] = $value;
            }
        }
        foreach ($bArgs as $key => $value) {
            if (!is_int($key)) {
                $aArgs[$key] = $value;
            }
        }

        try {
            $subtotal = (int) Helpers::validateNumericNullBool($functionType);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($subtotal > 100) {
            $aArgs = self::filterHiddenArgs($cellReference, $aArgs);
            $subtotal -= 100;
        }

        $aArgs = self::filterFormulaArgs($cellReference, $aArgs);
        if (array_key_exists($subtotal, self::CALL_FUNCTIONS)) {
            $call = self::CALL_FUNCTIONS[$subtotal];

            return call_user_func_array($call, $aArgs); 
        }

        return ExcelError::VALUE();
    }
}
