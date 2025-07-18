<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ChooseRowsEtc
{
    
    public static function transpose(array $array): array
    {
        return empty($array) ? [] : (array_map((count($array) === 1) ? (fn ($x) => [$x]) : null, ...$array)); 
    }

    
    private static function arrayValues(mixed $array): array
    {
        return is_array($array) ? array_values($array) : [$array];
    }

    
    public static function chooseCols(mixed $input, mixed ...$args): array|string
    {
        if (!is_array($input)) {
            $input = [[$input]];
        }
        $retval = self::chooseRows(self::transpose($input), ...$args);

        return is_array($retval) ? self::transpose($retval) : $retval;
    }

    
    public static function chooseRows(mixed $input, mixed ...$args): array|string
    {
        if (!is_array($input)) {
            $input = [[$input]];
        }
        $inputArray = [[]]; 
        $numRows = 0;
        foreach ($input as $inputRow) {
            $inputArray[] = self::arrayValues($inputRow);
            ++$numRows;
        }
        $outputArray = [];
        foreach (Functions::flattenArray2(...$args) as $arg) {
            if (!is_numeric($arg)) {
                return ExcelError::VALUE();
            }
            $index = (int) $arg;
            if ($index < 0) {
                $index += $numRows + 1;
            }
            if ($index <= 0 || $index > $numRows) {
                return ExcelError::VALUE();
            }
            $outputArray[] = $inputArray[$index];
        }

        return $outputArray;
    }

    private static function dropRows(array $array, mixed $offset): array|string
    {
        if ($offset === null) {
            return $array;
        }
        if (!is_numeric($offset)) {
            return ExcelError::VALUE();
        }
        $offset = (int) $offset;
        $count = count($array);
        if (abs($offset) >= $count) {
            
            
            return ExcelError::VALUE();
        }
        if ($offset === 0) {
            return $array;
        }
        if ($offset > 0) {
            return array_slice($array, $offset);
        }

        return array_slice($array, 0, $count + $offset);
    }

    
    public static function drop(mixed $input, mixed $rows = null, mixed $columns = null): array|string
    {
        if (!is_array($input)) {
            $input = [[$input]];
        }
        $inputArray = []; 
        foreach ($input as $inputRow) {
            $inputArray[] = self::arrayValues($inputRow);
        }
        $outputArray1 = self::dropRows($inputArray, $rows);
        if (is_string($outputArray1)) {
            return $outputArray1;
        }
        $outputArray2 = self::transpose($outputArray1);
        $outputArray3 = self::dropRows($outputArray2, $columns);
        if (is_string($outputArray3)) {
            return $outputArray3;
        }

        return self::transpose($outputArray3);
    }

    private static function takeRows(array $array, mixed $offset): array|string
    {
        if ($offset === null) {
            return $array;
        }
        if (!is_numeric($offset)) {
            return ExcelError::VALUE();
        }
        $offset = (int) $offset;
        if ($offset === 0) {
            
            return ExcelError::VALUE();
        }
        $count = count($array);
        if (abs($offset) >= $count) {
            return $array;
        }
        if ($offset > 0) {
            return array_slice($array, 0, $offset);
        }

        return array_slice($array, $count + $offset);
    }

    
    public static function take(mixed $input, mixed $rows, mixed $columns = null): array|string
    {
        if (!is_array($input)) {
            $input = [[$input]];
        }
        if ($rows === null && $columns === null) {
            return $input;
        }
        $inputArray = [];
        foreach ($input as $inputRow) {
            $inputArray[] = self::arrayValues($inputRow);
        }
        $outputArray1 = self::takeRows($inputArray, $rows);
        if (is_string($outputArray1)) {
            return $outputArray1;
        }
        $outputArray2 = self::transpose($outputArray1);
        $outputArray3 = self::takeRows($outputArray2, $columns);
        if (is_string($outputArray3)) {
            return $outputArray3;
        }

        return self::transpose($outputArray3);
    }

    
    public static function expand(mixed $input, mixed $rows, mixed $columns = null, mixed $pad = '
    {
        if (!is_array($input)) {
            $input = [[$input]];
        }
        if ($rows === null && $columns === null) {
            return $input;
        }
        $numRows = count($input);
        $rows ??= $numRows;
        if (!is_numeric($rows)) {
            return ExcelError::VALUE();
        }
        $rows = (int) $rows;
        if ($rows < count($input)) {
            return ExcelError::VALUE();
        }
        $numCols = 0;
        foreach ($input as $inputRow) {
            $numCols = max($numCols, is_array($inputRow) ? count($inputRow) : 1);
        }
        $columns ??= $numCols;
        if (!is_numeric($columns)) {
            return ExcelError::VALUE();
        }
        $columns = (int) $columns;
        if ($columns < $numCols) {
            return ExcelError::VALUE();
        }
        $inputArray = [];
        foreach ($input as $inputRow) {
            $inputArray[] = array_pad(self::arrayValues($inputRow), $columns, $pad);
        }
        $outputArray = [];
        $padRow = array_pad([], $columns, $pad);
        for ($count = 0; $count < $rows; ++$count) {
            $outputArray[] = ($count >= $numRows) ? $padRow : $inputArray[$count];
        }

        return $outputArray;
    }
}
