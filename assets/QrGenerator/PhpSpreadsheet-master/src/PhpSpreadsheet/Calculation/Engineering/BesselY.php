<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class BesselY
{
    use ArrayEnabled;

    
    public static function BESSELY(mixed $x, mixed $ord): array|string|float
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

        if (($ord < 0) || ($x <= 0.0)) {
            return ExcelError::NAN();
        }

        $fBy = self::calculate($x, $ord);

        return (is_nan($fBy)) ? ExcelError::NAN() : $fBy;
    }

    private static function calculate(float $x, int $ord): float
    {
        return match ($ord) {
            0 => self::besselY0($x),
            1 => self::besselY1($x),
            default => self::besselY2($x, $ord),
        };
    }

    
    private static function callBesselJ(float $x, int $ord): float
    {
        $rslt = BesselJ::BESSELJ($x, $ord);
        if (!is_float($rslt)) {
            throw new Exception('Unexpected array or string');
        }

        return $rslt;
    }

    private static function besselY0(float $x): float
    {
        if ($x < 8.0) {
            $y = ($x * $x);
            $ans1 = -2957821389.0 + $y * (7062834065.0 + $y * (-512359803.6 + $y * (10879881.29 + $y
                            * (-86327.92757 + $y * 228.4622733))));
            $ans2 = 40076544269.0 + $y * (745249964.8 + $y * (7189466.438 + $y
                        * (47447.26470 + $y * (226.1030244 + $y))));

            return $ans1 / $ans2 + 0.636619772 * self::callBesselJ($x, 0) * log($x);
        }

        $z = 8.0 / $x;
        $y = ($z * $z);
        $xx = $x - 0.785398164;
        $ans1 = 1 + $y * (-0.1098628627e-2 + $y * (0.2734510407e-4 + $y * (-0.2073370639e-5 + $y * 0.2093887211e-6)));
        $ans2 = -0.1562499995e-1 + $y * (0.1430488765e-3 + $y * (-0.6911147651e-5 + $y * (0.7621095161e-6 + $y
                        * (-0.934945152e-7))));

        return sqrt(0.636619772 / $x) * (sin($xx) * $ans1 + $z * cos($xx) * $ans2);
    }

    private static function besselY1(float $x): float
    {
        if ($x < 8.0) {
            $y = ($x * $x);
            $ans1 = $x * (-0.4900604943e13 + $y * (0.1275274390e13 + $y * (-0.5153438139e11 + $y
                            * (0.7349264551e9 + $y * (-0.4237922726e7 + $y * 0.8511937935e4)))));
            $ans2 = 0.2499580570e14 + $y * (0.4244419664e12 + $y * (0.3733650367e10 + $y * (0.2245904002e8 + $y
                            * (0.1020426050e6 + $y * (0.3549632885e3 + $y)))));

            return ($ans1 / $ans2) + 0.636619772 * (self::callBesselJ($x, 1) * log($x) - 1 / $x);
        }

        $z = 8.0 / $x;
        $y = $z * $z;
        $xx = $x - 2.356194491;
        $ans1 = 1.0 + $y * (0.183105e-2 + $y * (-0.3516396496e-4 + $y * (0.2457520174e-5 + $y * (-0.240337019e-6))));
        $ans2 = 0.04687499995 + $y * (-0.2002690873e-3 + $y * (0.8449199096e-5 + $y
                    * (-0.88228987e-6 + $y * 0.105787412e-6)));

        return sqrt(0.636619772 / $x) * (sin($xx) * $ans1 + $z * cos($xx) * $ans2);
    }

    private static function besselY2(float $x, int $ord): float
    {
        $fTox = 2.0 / $x;
        $fBym = self::besselY0($x);
        $fBy = self::besselY1($x);
        for ($n = 1; $n < $ord; ++$n) {
            $fByp = $n * $fTox * $fBy - $fBym;
            $fBym = $fBy;
            $fBy = $fByp;
        }

        return $fBy;
    }
}
