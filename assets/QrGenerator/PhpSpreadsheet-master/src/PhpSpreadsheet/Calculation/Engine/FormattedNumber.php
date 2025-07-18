<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engine;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class FormattedNumber
{
    
    
    private const STRING_REGEXP_FRACTION = '~^\s*(-?)((\d*)\s+)?(\d+\/\d+)\s*$~';

    private const STRING_REGEXP_PERCENT = '~^(?:(?: *(?<PrefixedSign>[-+])? *\% *(?<PrefixedSign2>[-+])? *(?<PrefixedValue>[0-9]+\.?[0-9*]*(?:E[-+]?[0-9]*)?) *)|(?: *(?<PostfixedSign>[-+])? *(?<PostfixedValue>[0-9]+\.?[0-9]*(?:E[-+]?[0-9]*)?) *\% *))$~i';

    
    private const CURRENCY_CONVERSION_LIST = '\$€£¥%s';

    private const STRING_CONVERSION_LIST = [
        [self::class, 'convertToNumberIfNumeric'],
        [self::class, 'convertToNumberIfFraction'],
        [self::class, 'convertToNumberIfPercent'],
        [self::class, 'convertToNumberIfCurrency'],
    ];

    
    public static function convertToNumberIfFormatted(string &$operand): bool
    {
        foreach (self::STRING_CONVERSION_LIST as $conversionMethod) {
            if ($conversionMethod($operand) === true) {
                return true;
            }
        }

        return false;
    }

    
    public static function convertToNumberIfNumeric(float|string &$operand): bool
    {
        $thousandsSeparator = preg_quote(StringHelper::getThousandsSeparator(), '/');
        $value = preg_replace(['/(\d)' . $thousandsSeparator . '(\d)/u', '/([+-])\s+(\d)/u'], ['$1$2', '$1$2'], trim("$operand"));
        $decimalSeparator = preg_quote(StringHelper::getDecimalSeparator(), '/');
        $value = preg_replace(['/(\d)' . $decimalSeparator . '(\d)/u', '/([+-])\s+(\d)/u'], ['$1.$2', '$1$2'], $value ?? '');

        if (is_numeric($value)) {
            $operand = (float) $value;

            return true;
        }

        return false;
    }

    
    public static function convertToNumberIfFraction(string &$operand): bool
    {
        if (preg_match(self::STRING_REGEXP_FRACTION, $operand, $match)) {
            $sign = ($match[1] === '-') ? '-' : '+';
            $wholePart = ($match[3] === '') ? '' : ($sign . $match[3]);
            $fractionFormula = '=' . $wholePart . $sign . $match[4];
            
            $operandx = Calculation::getInstance()->_calculateFormulaValue($fractionFormula);
            $operand = $operandx;

            return true;
        }

        return false;
    }

    
    public static function convertToNumberIfPercent(float|string &$operand): bool
    {
        $thousandsSeparator = preg_quote(StringHelper::getThousandsSeparator(), '/');
        $value = preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', trim("$operand"));
        $decimalSeparator = preg_quote(StringHelper::getDecimalSeparator(), '/');
        $value = preg_replace(['/(\d)' . $decimalSeparator . '(\d)/u', '/([+-])\s+(\d)/u'], ['$1.$2', '$1$2'], $value ?? '');

        $match = [];
        if ($value !== null && preg_match(self::STRING_REGEXP_PERCENT, $value, $match, PREG_UNMATCHED_AS_NULL)) {
            
            $sign = ($match['PrefixedSign'] ?? $match['PrefixedSign2'] ?? $match['PostfixedSign']) ?? '';
            $operand = (float) ($sign . ($match['PostfixedValue'] ?? $match['PrefixedValue'])) / 100;

            return true;
        }

        return false;
    }

    
    public static function convertToNumberIfCurrency(float|string &$operand): bool
    {
        $currencyRegexp = self::currencyMatcherRegexp();
        $thousandsSeparator = preg_quote(StringHelper::getThousandsSeparator(), '/');
        $value = preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', "$operand");

        $match = [];
        if ($value !== null && preg_match($currencyRegexp, $value, $match, PREG_UNMATCHED_AS_NULL)) {
            
            $sign = ($match['PrefixedSign'] ?? $match['PrefixedSign2'] ?? $match['PostfixedSign']) ?? '';
            $decimalSeparator = StringHelper::getDecimalSeparator();
            
            $intermediate = (string) ($match['PostfixedValue'] ?? $match['PrefixedValue']);
            $intermediate = str_replace($decimalSeparator, '.', $intermediate);
            if (is_numeric($intermediate)) {
                $operand = (float) ($sign . str_replace($decimalSeparator, '.', $intermediate));

                return true;
            }
        }

        return false;
    }

    public static function currencyMatcherRegexp(): string
    {
        $currencyCodes = sprintf(self::CURRENCY_CONVERSION_LIST, preg_quote(StringHelper::getCurrencyCode(), '/'));
        $decimalSeparator = preg_quote(StringHelper::getDecimalSeparator(), '/');

        return '~^(?:(?: *(?<PrefixedSign>[-+])? *(?<PrefixedCurrency>[' . $currencyCodes . ']) *(?<PrefixedSign2>[-+])? *(?<PrefixedValue>[0-9]+[' . $decimalSeparator . ']?[0-9*]*(?:E[-+]?[0-9]*)?) *)|(?: *(?<PostfixedSign>[-+])? *(?<PostfixedValue>[0-9]+' . $decimalSeparator . '?[0-9]*(?:E[-+]?[0-9]*)?) *(?<PostfixedCurrency>[' . $currencyCodes . ']) *))$~ui';
    }
}
