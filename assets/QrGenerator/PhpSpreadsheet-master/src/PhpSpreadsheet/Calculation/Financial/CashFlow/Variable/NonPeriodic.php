<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Variable;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class NonPeriodic
{
    const FINANCIAL_MAX_ITERATIONS = 128;

    const FINANCIAL_PRECISION = 1.0e-08;

    const DEFAULT_GUESS = 0.1;

    
    public static function rate(mixed $values, $dates, mixed $guess = self::DEFAULT_GUESS): float|string
    {
        $rslt = self::xirrPart1($values, $dates);
        
        if ($rslt !== '') {
            return $rslt;
        }

        
        $guess = Functions::flattenSingleValue($guess) ?? self::DEFAULT_GUESS;
        if (!is_numeric($guess)) {
            return ExcelError::VALUE();
        }
        $guess = ($guess + 0.0) ?: self::DEFAULT_GUESS;
        $x1 = 0.0;
        $x2 = $guess + 0.0;
        $f1 = self::xnpvOrdered($x1, $values, $dates, false);
        $f2 = self::xnpvOrdered($x2, $values, $dates, false);
        $found = false;
        for ($i = 0; $i < self::FINANCIAL_MAX_ITERATIONS; ++$i) {
            if (!is_numeric($f1)) {
                return $f1;
            }
            if (!is_numeric($f2)) {
                return $f2;
            }
            $f1 = (float) $f1;
            $f2 = (float) $f2;
            if (($f1 * $f2) < 0.0) {
                $found = true;

                break;
            } elseif (abs($f1) < abs($f2)) {
                $x1 += 1.6 * ($x1 - $x2);
                $f1 = self::xnpvOrdered($x1, $values, $dates, false);
            } else {
                $x2 += 1.6 * ($x2 - $x1);
                $f2 = self::xnpvOrdered($x2, $values, $dates, false);
            }
        }
        if ($found) {
            return self::xirrPart3($values, $dates, $x1, $x2);
        }

        
        $x1 = $guess - 0.5;
        $x2 = $guess + 0.5;
        for ($i = 0; $i < self::FINANCIAL_MAX_ITERATIONS; ++$i) {
            $f1 = self::xnpvOrdered($x1, $values, $dates, false, true);
            $f2 = self::xnpvOrdered($x2, $values, $dates, false, true);
            if (!is_numeric($f1) || !is_numeric($f2)) {
                break;
            }
            if ($f1 * $f2 <= 0) {
                $found = true;

                break;
            }
            $x1 -= 0.5;
            $x2 += 0.5;
        }
        if ($found) {
            return self::xirrBisection($values, $dates, $x1, $x2);
        }

        return ExcelError::NAN();
    }

    
    public static function presentValue(mixed $rate, mixed $values, mixed $dates): float|string
    {
        return self::xnpvOrdered($rate, $values, $dates, true);
    }

    private static function bothNegAndPos(bool $neg, bool $pos): bool
    {
        return $neg && $pos;
    }

    
    private static function xirrPart1(mixed &$values, mixed &$dates): string
    {
        $values = Functions::flattenArray($values); /
    private static function xirrPart2(array &$values): string
    {
        $valCount = count($values);
        $foundpos = false;
        $foundneg = false;
        for ($i = 0; $i < $valCount; ++$i) {
            $fld = $values[$i];
            if (!is_numeric($fld)) { /
    private static function xnpvOrdered(mixed $rate, mixed $values, mixed $dates, bool $ordered = true, bool $capAtNegative1 = false): float|string
    {
        $rate = Functions::flattenSingleValue($rate);
        if (!is_numeric($rate)) {
            return ExcelError::VALUE();
        }
        $values = Functions::flattenArray($values);
        $dates = Functions::flattenArray($dates);
        $valCount = count($values);

        try {
            self::validateXnpv($rate, $values, $dates);
            if ($capAtNegative1 && $rate <= -1) {
                $rate = -1.0 + 1.0E-10;
            }
            $date0 = DateTimeExcel\Helpers::getDateValue($dates[0]);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $xnpv = 0.0;
        for ($i = 0; $i < $valCount; ++$i) {
            if (!is_numeric($values[$i])) {
                return ExcelError::VALUE();
            }

            try {
                $datei = DateTimeExcel\Helpers::getDateValue($dates[$i]);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            if ($date0 > $datei) {
                $dif = $ordered ? ExcelError::NAN() : -((int) DateTimeExcel\Difference::interval($datei, $date0, 'd'));
            } else {
                $dif = Functions::scalar(DateTimeExcel\Difference::interval($date0, $datei, 'd'));
            }
            if (!is_numeric($dif)) {
                return StringHelper::convertToString($dif);
            }
            if ($rate <= -1.0) {
                $xnpv += -abs($values[$i] + 0) / (-1 - $rate) ** ($dif / 365);
            } else {
                $xnpv += $values[$i] / (1 + $rate) ** ($dif / 365);
            }
        }

        return is_finite($xnpv) ? $xnpv : ExcelError::VALUE();
    }

    private static function validateXnpv(mixed $rate, array $values, array $dates): void
    {
        if (!is_numeric($rate)) {
            throw new Exception(ExcelError::VALUE());
        }
        $valCount = count($values);
        if ($valCount != count($dates)) {
            throw new Exception(ExcelError::NAN());
        }
        if (count($values) > 1 && ((min($values) > 0) || (max($values) < 0))) {
            throw new Exception(ExcelError::NAN());
        }
    }
}
