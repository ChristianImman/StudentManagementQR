<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engine;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class ArrayArgumentProcessor
{
    private static ArrayArgumentHelper $arrayArgumentHelper;

    
    public static function processArguments(
        ArrayArgumentHelper $arrayArgumentHelper,
        callable $method,
        mixed ...$arguments
    ): array {
        self::$arrayArgumentHelper = $arrayArgumentHelper;

        if (self::$arrayArgumentHelper->hasArrayArgument() === false) {
            return [$method(...$arguments)];
        }

        if (self::$arrayArgumentHelper->arrayArguments() === 1) {
            $nthArgument = self::$arrayArgumentHelper->getFirstArrayArgumentNumber();

            return self::evaluateNthArgumentAsArray($method, $nthArgument, ...$arguments);
        }

        $singleRowVectorIndex = self::$arrayArgumentHelper->getSingleRowVector();
        $singleColumnVectorIndex = self::$arrayArgumentHelper->getSingleColumnVector();

        if ($singleRowVectorIndex !== null && $singleColumnVectorIndex !== null) {
            
            return self::evaluateVectorPair($method, $singleRowVectorIndex, $singleColumnVectorIndex, ...$arguments);
        }

        $matrixPair = self::$arrayArgumentHelper->getMatrixPair();
        if ($matrixPair !== []) {
            if (
                (self::$arrayArgumentHelper->isVector($matrixPair[0]) === true
                    && self::$arrayArgumentHelper->isVector($matrixPair[1]) === false)
                || (self::$arrayArgumentHelper->isVector($matrixPair[0]) === false
                    && self::$arrayArgumentHelper->isVector($matrixPair[1]) === true)
            ) {
                
                return self::evaluateVectorMatrixPair($method, $matrixPair, ...$arguments);
            }

            
            return self::evaluateMatrixPair($method, $matrixPair, ...$arguments);
        }

        
        
        return ['
    }

    
    private static function evaluateVectorMatrixPair(callable $method, array $matrixIndexes, mixed ...$arguments): array
    {
        $matrix2 = array_pop($matrixIndexes) ?? throw new Exception('empty array 2');
        
        $matrixValues2 = $arguments[$matrix2];
        $matrix1 = array_pop($matrixIndexes) ?? throw new Exception('empty array 1');
        
        $matrixValues1 = $arguments[$matrix1];

        
        $matrix12 = [$matrix1, $matrix2];
        $rows = min(array_map(self::$arrayArgumentHelper->rowCount(...), $matrix12));
        $columns = min(array_map(self::$arrayArgumentHelper->columnCount(...), $matrix12));

        if ($rows === 1) {
            $rows = max(array_map(self::$arrayArgumentHelper->rowCount(...), $matrix12));
        }
        if ($columns === 1) {
            $columns = max(array_map(self::$arrayArgumentHelper->columnCount(...), $matrix12));
        }

        $result = [];
        for ($rowIndex = 0; $rowIndex < $rows; ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $columns; ++$columnIndex) {
                $rowIndex1 = self::$arrayArgumentHelper->isRowVector($matrix1) ? 0 : $rowIndex;
                $columnIndex1 = self::$arrayArgumentHelper->isColumnVector($matrix1) ? 0 : $columnIndex;
                $value1 = $matrixValues1[$rowIndex1][$columnIndex1];
                $rowIndex2 = self::$arrayArgumentHelper->isRowVector($matrix2) ? 0 : $rowIndex;
                $columnIndex2 = self::$arrayArgumentHelper->isColumnVector($matrix2) ? 0 : $columnIndex;
                $value2 = $matrixValues2[$rowIndex2][$columnIndex2];
                $arguments[$matrix1] = $value1;
                $arguments[$matrix2] = $value2;

                $result[$rowIndex][$columnIndex] = $method(...$arguments);
            }
        }

        return $result;
    }

    
    private static function evaluateMatrixPair(callable $method, array $matrixIndexes, mixed ...$arguments): array
    {
        $matrix2 = array_pop($matrixIndexes);
        
        $matrixValues2 = $arguments[$matrix2];
        $matrix1 = array_pop($matrixIndexes);
        
        $matrixValues1 = $arguments[$matrix1];

        $result = [];
        foreach ($matrixValues1 as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value1) {
                if (isset($matrixValues2[$rowIndex][$columnIndex]) === false) {
                    continue;
                }

                $value2 = $matrixValues2[$rowIndex][$columnIndex];
                $arguments[$matrix1] = $value1;
                $arguments[$matrix2] = $value2;

                $result[$rowIndex][$columnIndex] = $method(...$arguments);
            }
        }

        return $result;
    }

    
    private static function evaluateVectorPair(callable $method, int $rowIndex, int $columnIndex, mixed ...$arguments): array
    {
        $rowVector = Functions::flattenArray($arguments[$rowIndex]);
        $columnVector = Functions::flattenArray($arguments[$columnIndex]);

        $result = [];
        foreach ($columnVector as $column) {
            $rowResults = [];
            foreach ($rowVector as $row) {
                $arguments[$rowIndex] = $row;
                $arguments[$columnIndex] = $column;

                $rowResults[] = $method(...$arguments);
            }
            $result[] = $rowResults;
        }

        return $result;
    }

    
    private static function evaluateNthArgumentAsArray(callable $method, int $nthArgument, mixed ...$arguments): array
    {
        $values = array_slice($arguments, $nthArgument - 1, 1);
        
        $values = array_pop($values);

        $result = [];
        foreach ($values as $value) {
            $arguments[$nthArgument - 1] = $value;
            $result[] = $method(...$arguments);
        }

        return $result;
    }
}
