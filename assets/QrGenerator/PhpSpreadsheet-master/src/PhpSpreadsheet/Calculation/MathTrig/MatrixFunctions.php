<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use Matrix\Builder;
use Matrix\Div0Exception as MatrixDiv0Exception;
use Matrix\Exception as MatrixException;
use Matrix\Matrix;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class MatrixFunctions
{
    
    private static function getMatrix(mixed $matrixValues): Matrix
    {
        $matrixData = [];
        if (!is_array($matrixValues)) {
            $matrixValues = [[$matrixValues]];
        }

        $row = 0;
        foreach ($matrixValues as $matrixRow) {
            if (!is_array($matrixRow)) {
                $matrixRow = [$matrixRow];
            }
            $column = 0;
            foreach ($matrixRow as $matrixCell) {
                if ((is_string($matrixCell)) || ($matrixCell === null)) {
                    throw new Exception(ExcelError::VALUE());
                }
                $matrixData[$row][$column] = $matrixCell;
                ++$column;
            }
            ++$row;
        }

        return new Matrix($matrixData);
    }

    
    public static function sequence(mixed $rows = 1, mixed $columns = 1, mixed $start = 1, mixed $step = 1): string|array
    {
        try {
            $rows = (int) Helpers::validateNumericNullSubstitution($rows, 1);
            Helpers::validatePositive($rows);
            $columns = (int) Helpers::validateNumericNullSubstitution($columns, 1);
            Helpers::validatePositive($columns);
            $start = Helpers::validateNumericNullSubstitution($start, 1);
            $step = Helpers::validateNumericNullSubstitution($step, 1);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($step === 0) {
            return array_chunk(
                array_fill(0, $rows * $columns, $start),
                max($columns, 1)
            );
        }

        return array_chunk(
            range($start, $start + (($rows * $columns - 1) * $step), $step),
            max($columns, 1)
        );
    }

    
    public static function determinant(mixed $matrixValues)
    {
        try {
            $matrix = self::getMatrix($matrixValues);

            return $matrix->determinant();
        } catch (MatrixException) {
            return ExcelError::VALUE();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    
    public static function inverse(mixed $matrixValues): array|string
    {
        try {
            $matrix = self::getMatrix($matrixValues);

            return $matrix->inverse()->toArray();
        } catch (MatrixDiv0Exception) {
            return ExcelError::NAN();
        } catch (MatrixException) {
            return ExcelError::VALUE();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    
    public static function multiply(mixed $matrixData1, mixed $matrixData2): array|string
    {
        try {
            $matrixA = self::getMatrix($matrixData1);
            $matrixB = self::getMatrix($matrixData2);

            return $matrixA->multiply($matrixB)->toArray();
        } catch (MatrixException) {
            return ExcelError::VALUE();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    
    public static function identity(mixed $dimension)
    {
        try {
            $dimension = (int) Helpers::validateNumericNullBool($dimension);
            Helpers::validatePositive($dimension, ExcelError::VALUE());
            $matrix = Builder::createIdentityMatrix($dimension, 0)->toArray();

            return $matrix;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
