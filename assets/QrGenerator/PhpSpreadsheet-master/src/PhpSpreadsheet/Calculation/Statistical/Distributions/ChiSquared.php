<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ChiSquared
{
    use ArrayEnabled;

    private const EPS = 2.22e-16;

    
    public static function distributionRightTail(mixed $value, mixed $degrees): array|string|int|float
    {
        if (is_array($value) || is_array($degrees)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $degrees);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $degrees = DistributionValidations::validateInt($degrees);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($degrees < 1) {
            return ExcelError::NAN();
        }
        if ($value < 0) {
            if (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_GNUMERIC) {
                return 1;
            }

            return ExcelError::NAN();
        }

        return 1 - (Gamma::incompleteGamma($degrees / 2, $value / 2) / Gamma::gammaValue($degrees / 2));
    }

    
    public static function distributionLeftTail(mixed $value, mixed $degrees, mixed $cumulative): array|string|int|float
    {
        if (is_array($value) || is_array($degrees) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $degrees, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $degrees = DistributionValidations::validateInt($degrees);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($degrees < 1) {
            return ExcelError::NAN();
        }
        if ($value < 0) {
            if (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_GNUMERIC) {
                return 1;
            }

            return ExcelError::NAN();
        }

        if ($cumulative === true) {
            $temp = self::distributionRightTail($value, $degrees);

            return 1 - (is_numeric($temp) ? $temp : 0);
        }

        return ($value ** (($degrees / 2) - 1) * exp(-$value / 2))
            / ((2 ** ($degrees / 2)) * Gamma::gammaValue($degrees / 2));
    }

    
    public static function inverseRightTail(mixed $probability, mixed $degrees)
    {
        if (is_array($probability) || is_array($degrees)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $degrees);
        }

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $degrees = DistributionValidations::validateInt($degrees);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($degrees < 1) {
            return ExcelError::NAN();
        }

        $callback = fn ($value): float => 1 - (Gamma::incompleteGamma($degrees / 2, $value / 2)
                    / Gamma::gammaValue($degrees / 2));

        $newtonRaphson = new NewtonRaphson($callback);

        return $newtonRaphson->execute($probability);
    }

    
    public static function inverseLeftTail(mixed $probability, mixed $degrees): array|string|float
    {
        if (is_array($probability) || is_array($degrees)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $degrees);
        }

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $degrees = DistributionValidations::validateInt($degrees);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($degrees < 1) {
            return ExcelError::NAN();
        }

        return self::inverseLeftTailCalculation($probability, $degrees);
    }

    
    public static function test($actual, $expected): float|string
    {
        $rows = count($actual);
        $actual = Functions::flattenArray($actual);
        $expected = Functions::flattenArray($expected);
        $columns = intdiv(count($actual), $rows);

        $countActuals = count($actual);
        $countExpected = count($expected);
        if ($countActuals !== $countExpected || $countActuals === 1) {
            return ExcelError::NAN();
        }

        $result = 0.0;
        for ($i = 0; $i < $countActuals; ++$i) {
            if ($expected[$i] == 0.0) {
                return ExcelError::DIV0();
            } elseif ($expected[$i] < 0.0) {
                return ExcelError::NAN();
            }
            $result += (($actual[$i] - $expected[$i]) ** 2) / $expected[$i];
        }

        $degrees = self::degrees($rows, $columns);

        
        $result = Functions::scalar(self::distributionRightTail($result, $degrees));

        return $result;
    }

    protected static function degrees(int $rows, int $columns): int
    {
        if ($rows === 1) {
            return $columns - 1;
        } elseif ($columns === 1) {
            return $rows - 1;
        }

        return ($columns - 1) * ($rows - 1);
    }

    private static function inverseLeftTailCalculation(float $probability, int $degrees): float
    {
        
        $min = 0;
        $sd = sqrt(2.0 * $degrees);
        $max = 2 * $sd;
        $s = -1;

        while ($s * self::pchisq($max, $degrees) > $probability * $s) {
            $min = $max;
            $max += 2 * $sd;
        }

        
        $chi2 = 0.5 * ($min + $max);

        while (($max - $min) > self::EPS * $chi2) {
            if ($s * self::pchisq($chi2, $degrees) > $probability * $s) {
                $min = $chi2;
            } else {
                $max = $chi2;
            }
            $chi2 = 0.5 * ($min + $max);
        }

        return $chi2;
    }

    private static function pchisq(float $chi2, int $degrees): float
    {
        return self::gammp($degrees, 0.5 * $chi2);
    }

    private static function gammp(int $n, float $x): float
    {
        if ($x < 0.5 * $n + 1) {
            return self::gser($n, $x);
        }

        return 1 - self::gcf($n, $x);
    }

    
    
    
    
    private static function gser(int $n, float $x): float
    {
        
        $gln = Gamma::ln($n / 2);
        $a = 0.5 * $n;
        $ap = $a;
        $sum = 1.0 / $a;
        $del = $sum;
        for ($i = 1; $i < 101; ++$i) {
            ++$ap;
            $del = $del * $x / $ap;
            $sum += $del;
            if ($del < $sum * self::EPS) {
                break;
            }
        }

        return $sum * exp(-$x + $a * log($x) - $gln);
    }

    
    
    
    
    private static function gcf(int $n, float $x): float
    {
        
        $gln = Gamma::ln($n / 2);
        $a = 0.5 * $n;
        $b = $x + 1 - $a;
        $fpmin = 1.e-300;
        $c = 1 / $fpmin;
        $d = 1 / $b;
        $h = $d;
        for ($i = 1; $i < 101; ++$i) {
            $an = -$i * ($i - $a);
            $b += 2;
            $d = $an * $d + $b;
            if (abs($d) < $fpmin) {
                $d = $fpmin;
            }
            $c = $b + $an / $c;
            if (abs($c) < $fpmin) {
                $c = $fpmin;
            }
            $d = 1 / $d;
            $del = $d * $c;
            $h = $h * $del;
            if (abs($del - 1) < self::EPS) {
                break;
            }
        }

        return $h * exp(-$x + $a * log($x) - $gln);
    }
}
