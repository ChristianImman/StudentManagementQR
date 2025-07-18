<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Arabic
{
    use ArrayEnabled;

    private const ROMAN_LOOKUP = [
        'M' => 1000,
        'D' => 500,
        'C' => 100,
        'L' => 50,
        'X' => 10,
        'V' => 5,
        'I' => 1,
    ];

    
    private static function calculateArabic(array $roman, int &$sum = 0, int $subtract = 0): int
    {
        $numeral = array_shift($roman);
        if (!isset(self::ROMAN_LOOKUP[$numeral])) {
            throw new Exception('Invalid character detected');
        }

        $arabic = self::ROMAN_LOOKUP[$numeral];
        if (count($roman) > 0 && isset(self::ROMAN_LOOKUP[$roman[0]]) && $arabic < self::ROMAN_LOOKUP[$roman[0]]) {
            $subtract += $arabic;
        } else {
            $sum += ($arabic - $subtract);
            $subtract = 0;
        }

        if (count($roman) > 0) {
            self::calculateArabic($roman, $sum, $subtract);
        }

        return $sum;
    }

    
    public static function evaluate(mixed $roman): array|int|string
    {
        if (is_array($roman)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $roman);
        }

        
        $roman = substr(trim(strtoupper((string) $roman)), 0, 255);
        if ($roman === '') {
            return 0;
        }

        
        $negativeNumber = $roman[0] === '-';
        if ($negativeNumber) {
            $roman = trim(substr($roman, 1));
            if ($roman === '') {
                return ExcelError::NAN();
            }
        }

        try {
            $arabic = self::calculateArabic(mb_str_split($roman, 1, 'UTF-8'));
        } catch (Exception) {
            return ExcelError::VALUE(); 
        }

        if ($negativeNumber) {
            $arabic *= -1; 
        }

        return $arabic;
    }
}
