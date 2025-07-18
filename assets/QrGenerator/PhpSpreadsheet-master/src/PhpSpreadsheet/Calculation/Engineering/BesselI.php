<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class BesselI
{
    use ArrayEnabled;

    
    public static function BESSELI(mixed $x, mixed $ord): array|string|float
    {
        if (is_array($x) || is_array($ord)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $x, $ord);
        }

        try {
            $x = EngineeringValidations::validateFloat($x);
            $ord = EngineeringValidations::validateInt($ord);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($ord < 0) {
            return ExcelError::NAN();
        }

        $fResult = self::calculate($x, $ord);

        return (is_nan($fResult)) ? ExcelError::NAN() : $fResult;
    }

    private static function calculate(float $x, int $ord): float
    {
        return match ($ord) {
            0 => self::besselI0($x),
            1 => self::besselI1($x),
            default => self::besselI2($x, $ord),
        };
    }

    private static function besselI0(float $x): float
    {
        $ax = abs($x);

        if ($ax < 3.75) {
            $y = $x / 3.75;
            $y = $y * $y;

            return 1.0 + $y * (3.5156229 + $y * (3.0899424 + $y * (1.2067492
                                + $y * (0.2659732 + $y * (0.360768e-1 + $y * 0.45813e-2)))));
        }

        $y = 3.75 / $ax;

        return (exp($ax) / sqrt($ax)) * (0.39894228 + $y * (0.1328592e-1 + $y * (0.225319e-2 + $y * (-0.157565e-2
                            + $y * (0.916281e-2 + $y * (-0.2057706e-1 + $y * (0.2635537e-1
                                        + $y * (-0.1647633e-1 + $y * 0.392377e-2))))))));
    }

    private static function besselI1(float $x): float
    {
        $ax = abs($x);

        if ($ax < 3.75) {
            $y = $x / 3.75;
            $y = $y * $y;
            $ans = $ax * (0.5 + $y * (0.87890594 + $y * (0.51498869 + $y * (0.15084934 + $y * (0.2658733e-1
                                    + $y * (0.301532e-2 + $y * 0.32411e-3))))));

            return ($x < 0.0) ? -$ans : $ans;
        }

        $y = 3.75 / $ax;
        $ans = 0.2282967e-1 + $y * (-0.2895312e-1 + $y * (0.1787654e-1 - $y * 0.420059e-2));
        $ans = 0.39894228 + $y * (-0.3988024e-1 + $y * (-0.362018e-2 + $y * (0.163801e-2
                        + $y * (-0.1031555e-1 + $y * $ans))));
        $ans *= exp($ax) / sqrt($ax);

        return ($x < 0.0) ? -$ans : $ans;
    }

    private static function besselI2(float $x, int $ord): float
    {
        if ($x === 0.0) {
            return 0.0;
        }

        $tox = 2.0 / abs($x);
        $bip = 0;
        $ans = 0.0;
        $bi = 1.0;

        for ($j = 2 * ($ord + (int) sqrt(40.0 * $ord)); $j > 0; --$j) {
            $bim = $bip + $j * $tox * $bi;
            $bip = $bi;
            $bi = $bim;

            if (abs($bi) > 1.0e+12) {
                $ans *= 1.0e-12;
                $bi *= 1.0e-12;
                $bip *= 1.0e-12;
            }

            if ($j === $ord) {
                $ans = $bip;
            }
        }

        $ans *= self::besselI0($x) / $bi;

        return ($x < 0.0 && (($ord % 2) === 1)) ? -$ans : $ans;
    }
}
