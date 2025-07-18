<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Beta
{
    use ArrayEnabled;

    private const MAX_ITERATIONS = 256;

    private const LOG_GAMMA_X_MAX_VALUE = 2.55e305;

    private const XMININ = 2.23e-308;

    
    public static function distribution(mixed $value, mixed $alpha, mixed $beta, mixed $rMin = 0.0, mixed $rMax = 1.0): array|string|float
    {
        if (is_array($value) || is_array($alpha) || is_array($beta) || is_array($rMin) || is_array($rMax)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $alpha, $beta, $rMin, $rMax);
        }

        $rMin = $rMin ?? 0.0;
        $rMax = $rMax ?? 1.0;

        try {
            $value = DistributionValidations::validateFloat($value);
            $alpha = DistributionValidations::validateFloat($alpha);
            $beta = DistributionValidations::validateFloat($beta);
            $rMax = DistributionValidations::validateFloat($rMax);
            $rMin = DistributionValidations::validateFloat($rMin);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($rMin > $rMax) {
            $tmp = $rMin;
            $rMin = $rMax;
            $rMax = $tmp;
        }
        if (($value < $rMin) || ($value > $rMax) || ($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax)) {
            return ExcelError::NAN();
        }

        $value -= $rMin;
        $value /= ($rMax - $rMin);

        return self::incompleteBeta($value, $alpha, $beta);
    }

    
    public static function inverse(mixed $probability, mixed $alpha, mixed $beta, mixed $rMin = 0.0, mixed $rMax = 1.0): array|string|float
    {
        if (is_array($probability) || is_array($alpha) || is_array($beta) || is_array($rMin) || is_array($rMax)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $alpha, $beta, $rMin, $rMax);
        }

        $rMin = $rMin ?? 0.0;
        $rMax = $rMax ?? 1.0;

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $alpha = DistributionValidations::validateFloat($alpha);
            $beta = DistributionValidations::validateFloat($beta);
            $rMax = DistributionValidations::validateFloat($rMax);
            $rMin = DistributionValidations::validateFloat($rMin);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($rMin > $rMax) {
            $tmp = $rMin;
            $rMin = $rMax;
            $rMax = $tmp;
        }
        if (($alpha <= 0) || ($beta <= 0) || ($rMin == $rMax) || ($probability <= 0.0)) {
            return ExcelError::NAN();
        }

        return self::calculateInverse($probability, $alpha, $beta, $rMin, $rMax);
    }

    private static function calculateInverse(float $probability, float $alpha, float $beta, float $rMin, float $rMax): string|float
    {
        $a = 0;
        $b = 2;
        $guess = ($a + $b) / 2;

        $i = 0;
        while ((($b - $a) > Functions::PRECISION) && (++$i <= self::MAX_ITERATIONS)) {
            $guess = ($a + $b) / 2;
            $result = self::distribution($guess, $alpha, $beta);
            if (($result === $probability) || ($result === 0.0)) {
                $b = $a;
            } elseif ($result > $probability) {
                $b = $guess;
            } else {
                $a = $guess;
            }
        }

        if ($i === self::MAX_ITERATIONS) {
            return ExcelError::NA();
        }

        return round($rMin + $guess * ($rMax - $rMin), 12);
    }

    
    public static function incompleteBeta(float $x, float $p, float $q): float
    {
        if ($x <= 0.0) {
            return 0.0;
        } elseif ($x >= 1.0) {
            return 1.0;
        } elseif (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > self::LOG_GAMMA_X_MAX_VALUE)) {
            return 0.0;
        }

        $beta_gam = exp((0 - self::logBeta($p, $q)) + $p * log($x) + $q * log(1.0 - $x));
        if ($x < ($p + 1.0) / ($p + $q + 2.0)) {
            return $beta_gam * self::betaFraction($x, $p, $q) / $p;
        }

        return 1.0 - ($beta_gam * self::betaFraction(1 - $x, $q, $p) / $q);
    }

    

    private static float $logBetaCacheP = 0.0;

    private static float $logBetaCacheQ = 0.0;

    private static float $logBetaCacheResult = 0.0;

    
    private static function logBeta(float $p, float $q): float
    {
        if ($p != self::$logBetaCacheP || $q != self::$logBetaCacheQ) {
            self::$logBetaCacheP = $p;
            self::$logBetaCacheQ = $q;
            if (($p <= 0.0) || ($q <= 0.0) || (($p + $q) > self::LOG_GAMMA_X_MAX_VALUE)) {
                self::$logBetaCacheResult = 0.0;
            } else {
                self::$logBetaCacheResult = Gamma::logGamma($p) + Gamma::logGamma($q) - Gamma::logGamma($p + $q);
            }
        }

        return self::$logBetaCacheResult;
    }

    
    private static function betaFraction(float $x, float $p, float $q): float
    {
        $c = 1.0;
        $sum_pq = $p + $q;
        $p_plus = $p + 1.0;
        $p_minus = $p - 1.0;
        $h = 1.0 - $sum_pq * $x / $p_plus;
        if (abs($h) < self::XMININ) {
            $h = self::XMININ;
        }
        $h = 1.0 / $h;
        $frac = $h;
        $m = 1;
        $delta = 0.0;
        while ($m <= self::MAX_ITERATIONS && abs($delta - 1.0) > Functions::PRECISION) {
            $m2 = 2 * $m;
            
            $d = $m * ($q - $m) * $x / (($p_minus + $m2) * ($p + $m2));
            $h = 1.0 + $d * $h;
            if (abs($h) < self::XMININ) {
                $h = self::XMININ;
            }
            $h = 1.0 / $h;
            $c = 1.0 + $d / $c;
            if (abs($c) < self::XMININ) {
                $c = self::XMININ;
            }
            $frac *= $h * $c;
            
            $d = -($p + $m) * ($sum_pq + $m) * $x / (($p + $m2) * ($p_plus + $m2));
            $h = 1.0 + $d * $h;
            if (abs($h) < self::XMININ) {
                $h = self::XMININ;
            }
            $h = 1.0 / $h;
            $c = 1.0 + $d / $c;
            if (abs($c) < self::XMININ) {
                $c = self::XMININ;
            }
            $delta = $h * $c;
            $frac *= $delta;
            ++$m;
        }

        return $frac;
    }

    
}
