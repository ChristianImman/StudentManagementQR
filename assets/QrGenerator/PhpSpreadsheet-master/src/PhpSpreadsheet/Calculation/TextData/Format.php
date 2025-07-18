<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use Composer\Pcre\Preg;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Format
{
    use ArrayEnabled;

    
    public static function DOLLAR(mixed $value = 0, mixed $decimals = 2)
    {
        if (is_array($value) || is_array($decimals)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimals);
        }

        try {
            $value = Helpers::extractFloat($value);
            $decimals = Helpers::extractInt($decimals, -100, 0, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        $mask = '$
        if ($decimals > 0) {
            $mask .= '.' . str_repeat('0', $decimals);
        } else {
            $round = 10 ** abs($decimals);
            if ($value < 0) {
                $round = 0 - $round;
            }
            
            $value = MathTrig\Round::multiple($value, $round);
        }
        $mask = "{$mask};-{$mask}";

        return NumberFormat::toFormattedString($value, $mask);
    }

    
    public static function FIXEDFORMAT(mixed $value, mixed $decimals = 2, mixed $noCommas = false): array|string
    {
        if (is_array($value) || is_array($decimals) || is_array($noCommas)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimals, $noCommas);
        }

        try {
            $value = Helpers::extractFloat($value);
            $decimals = Helpers::extractInt($decimals, -100, 0, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        $valueResult = round($value, $decimals);
        if ($decimals < 0) {
            $decimals = 0;
        }
        if ($noCommas === false) {
            $valueResult = number_format(
                $valueResult,
                $decimals,
                StringHelper::getDecimalSeparator(),
                StringHelper::getThousandsSeparator()
            );
        }

        return (string) $valueResult;
    }

    
    public static function TEXTFORMAT(mixed $value, mixed $format): array|string
    {
        if (is_array($value) || is_array($format)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $format);
        }

        try {
            $value = Helpers::extractString($value, true);
            $format = Helpers::extractString($format, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        $format = (string) NumberFormat::convertSystemFormats($format);

        if (!is_numeric($value) && Date::isDateTimeFormatCode($format) && !Preg::isMatch('/^\s*\d+(\s+\d+)+\s*$/', $value)) {
            $value1 = DateTimeExcel\DateValue::fromString($value);
            $value2 = DateTimeExcel\TimeValue::fromString($value);
            
            $value = (is_numeric($value1) && is_numeric($value2)) ? ($value1 + $value2) : (is_numeric($value1) ? $value1 : (is_numeric($value2) ? $value2 : $value));
        }

        return (string) NumberFormat::toFormattedString($value, $format);
    }

    
    private static function convertValue(mixed $value, bool $spacesMeanZero = false): mixed
    {
        $value = $value ?? 0;
        if (is_bool($value)) {
            if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
                $value = (int) $value;
            } else {
                throw new CalcExp(ExcelError::VALUE());
            }
        }
        if (is_string($value)) {
            $value = trim($value);
            if (ErrorValue::isError($value, true)) {
                throw new CalcExp($value);
            }
            if ($spacesMeanZero && $value === '') {
                $value = 0;
            }
        }

        return $value;
    }

    
    public static function VALUE(mixed $value = '')
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = self::convertValue($value);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }
        if (!is_numeric($value)) {
            $value = StringHelper::convertToString($value);
            $numberValue = str_replace(
                StringHelper::getThousandsSeparator(),
                '',
                trim($value, " \t\n\r\0\x0B" . StringHelper::getCurrencyCode())
            );
            if ($numberValue === '') {
                return ExcelError::VALUE();
            }
            if (is_numeric($numberValue)) {
                return (float) $numberValue;
            }

            $dateSetting = Functions::getReturnDateType();
            Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);

            if (str_contains($value, ':')) {
                $timeValue = Functions::scalar(DateTimeExcel\TimeValue::fromString($value));
                if ($timeValue !== ExcelError::VALUE()) {
                    Functions::setReturnDateType($dateSetting);

                    return $timeValue; /
    public static function valueToText(mixed $value, mixed $format = false): array|string
    {
        if (is_array($value) || is_array($format)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $format);
        }

        $format = (bool) $format;

        if (is_object($value) && $value instanceof RichText) {
            $value = $value->getPlainText();
        }
        if (is_string($value)) {
            $value = ($format === true) ? StringHelper::convertToString(Calculation::wrapResult($value)) : $value;
            $value = str_replace("\n", '', $value);
        } elseif (is_bool($value)) {
            $value = Calculation::getLocaleBoolean($value ? 'TRUE' : 'FALSE');
        }

        return StringHelper::convertToString($value);
    }

    private static function getDecimalSeparator(mixed $decimalSeparator): string
    {
        return empty($decimalSeparator) ? StringHelper::getDecimalSeparator() : StringHelper::convertToString($decimalSeparator);
    }

    private static function getGroupSeparator(mixed $groupSeparator): string
    {
        return empty($groupSeparator) ? StringHelper::getThousandsSeparator() : StringHelper::convertToString($groupSeparator);
    }

    
    public static function NUMBERVALUE(mixed $value = '', mixed $decimalSeparator = null, mixed $groupSeparator = null): array|string|float
    {
        if (is_array($value) || is_array($decimalSeparator) || is_array($groupSeparator)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimalSeparator, $groupSeparator);
        }

        try {
            $value = self::convertValue($value, true);
            $decimalSeparator = self::getDecimalSeparator($decimalSeparator);
            $groupSeparator = self::getGroupSeparator($groupSeparator);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        
        if (!is_array($value) && !is_numeric($value)) {
            $value = StringHelper::convertToString($value);
            $decimalPositions = Preg::matchAllWithOffsets('/' . preg_quote($decimalSeparator, '/') . '/', $value, $matches);
            if ($decimalPositions > 1) {
                return ExcelError::VALUE();
            }
            $decimalOffset = array_pop($matches[0])[1] ?? null;
            if ($decimalOffset === null || strpos($value, $groupSeparator, $decimalOffset) !== false) {
                return ExcelError::VALUE();
            }

            $value = str_replace([$groupSeparator, $decimalSeparator], ['', '.'], $value);

            
            $percentageString = rtrim($value, '%');
            if (!is_numeric($percentageString)) {
                return ExcelError::VALUE();
            }

            $percentageAdjustment = strlen($value) - strlen($percentageString);
            if ($percentageAdjustment) {
                $value = (float) $percentageString;
                $value /= 10 ** ($percentageAdjustment * 2);
            }
        }

        return is_array($value) ? ExcelError::VALUE() : (float) $value;
    }
}
