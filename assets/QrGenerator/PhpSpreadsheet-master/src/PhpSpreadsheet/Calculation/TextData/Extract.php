<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Extract
{
    use ArrayEnabled;

    
    public static function left(mixed $value, mixed $chars = 1): array|string
    {
        if (is_array($value) || is_array($chars)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $chars);
        }

        try {
            $value = Helpers::extractString($value, true);
            $chars = Helpers::extractInt($chars, 0, 1);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return mb_substr($value, 0, $chars, 'UTF-8');
    }

    
    public static function mid(mixed $value, mixed $start, mixed $chars): array|string
    {
        if (is_array($value) || is_array($start) || is_array($chars)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $start, $chars);
        }

        try {
            $value = Helpers::extractString($value, true);
            $start = Helpers::extractInt($start, 1);
            $chars = Helpers::extractInt($chars, 0);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return mb_substr($value, --$start, $chars, 'UTF-8');
    }

    
    public static function right(mixed $value, mixed $chars = 1): array|string
    {
        if (is_array($value) || is_array($chars)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $chars);
        }

        try {
            $value = Helpers::extractString($value, true);
            $chars = Helpers::extractInt($chars, 0, 1);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return mb_substr($value, mb_strlen($value, 'UTF-8') - $chars, $chars, 'UTF-8');
    }

    
    public static function before(mixed $text, $delimiter, mixed $instance = 1, mixed $matchMode = 0, mixed $matchEnd = 0, mixed $ifNotFound = '
    {
        if (is_array($text) || is_array($instance) || is_array($matchMode) || is_array($matchEnd) || is_array($ifNotFound)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], 1, $text, $delimiter, $instance, $matchMode, $matchEnd, $ifNotFound);
        }

        try {
            $text = Helpers::extractString($text ?? '', true);
            Helpers::extractString(Functions::flattenSingleValue($delimiter ?? ''), true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        $instance = (int) StringHelper::convertToString($instance);
        $matchMode = (int) StringHelper::convertToString($matchMode);
        $matchEnd = (int) StringHelper::convertToString($matchEnd);

        $split = self::validateTextBeforeAfter($text, $delimiter, $instance, $matchMode, $matchEnd, $ifNotFound);
        if (is_string($split)) {
            return $split;
        }
        if (Helpers::extractString(Functions::flattenSingleValue($delimiter ?? '')) === '') {
            return ($instance > 0) ? '' : $text;
        }

        
        $flags = self::matchFlags($matchMode);
        $delimiter = self::buildDelimiter($delimiter);
        $adjust = preg_match('/^' . $delimiter . "\$/{$flags}", $split[0]);
        $oddReverseAdjustment = count($split) % 2;

        $split = ($instance < 0)
            ? array_slice($split, 0, max(count($split) - (abs($instance) * 2 - 1) - $adjust - $oddReverseAdjustment, 0))
            : array_slice($split, 0, $instance * 2 - 1 - $adjust);

        return implode('', $split);
    }

    
    public static function after(mixed $text, $delimiter, mixed $instance = 1, mixed $matchMode = 0, mixed $matchEnd = 0, mixed $ifNotFound = '
    {
        if (is_array($text) || is_array($instance) || is_array($matchMode) || is_array($matchEnd) || is_array($ifNotFound)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], 1, $text, $delimiter, $instance, $matchMode, $matchEnd, $ifNotFound);
        }

        try {
            $text = Helpers::extractString($text ?? '', true);
            Helpers::extractString(Functions::flattenSingleValue($delimiter ?? ''), true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        $instance = (int) StringHelper::convertToString($instance);
        $matchMode = (int) StringHelper::convertToString($matchMode);
        $matchEnd = (int) StringHelper::convertToString($matchEnd);

        $split = self::validateTextBeforeAfter($text, $delimiter, $instance, $matchMode, $matchEnd, $ifNotFound);
        if (is_string($split)) {
            return $split;
        }
        if (Helpers::extractString(Functions::flattenSingleValue($delimiter ?? '')) === '') {
            return ($instance < 0) ? '' : $text;
        }

        
        $flags = self::matchFlags($matchMode);
        $delimiter = self::buildDelimiter($delimiter);
        $adjust = preg_match('/^' . $delimiter . "\$/{$flags}", $split[0]);
        $oddReverseAdjustment = count($split) % 2;

        $split = ($instance < 0)
            ? array_slice($split, count($split) - ((int) abs($instance + 1) * 2) - $adjust - $oddReverseAdjustment)
            : array_slice($split, $instance * 2 - $adjust);

        return implode('', $split);
    }

    private static function validateTextBeforeAfter(string $text, null|array|string $delimiter, int $instance, int $matchMode, int $matchEnd, mixed $ifNotFound): array|string
    {
        $flags = self::matchFlags($matchMode);
        $delimiter = self::buildDelimiter($delimiter);

        if (preg_match('/' . $delimiter . "/{$flags}", $text) === 0 && $matchEnd === 0) {
            return is_array($ifNotFound) ? $ifNotFound : StringHelper::convertToString($ifNotFound);
        }

        $split = preg_split('/' . $delimiter . "/{$flags}", $text, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if ($split === false) {
            return ExcelError::NA();
        }

        if ($instance === 0 || abs($instance) > StringHelper::countCharacters($text)) {
            return ExcelError::VALUE();
        }

        if ($matchEnd === 0 && (abs($instance) > floor(count($split) / 2))) {
            return ExcelError::NA();
        } elseif ($matchEnd !== 0 && (abs($instance) - 1 > ceil(count($split) / 2))) {
            return ExcelError::NA();
        }

        return $split;
    }

    
    private static function buildDelimiter($delimiter): string
    {
        if (is_array($delimiter)) {
            $delimiter = Functions::flattenArray($delimiter);
            $quotedDelimiters = array_map(
                fn ($delimiter): string => preg_quote($delimiter ?? '', '/'),
                $delimiter
            );
            $delimiters = implode('|', $quotedDelimiters);

            return '(' . $delimiters . ')';
        }

        return '(' . preg_quote($delimiter ?? '', '/') . ')';
    }

    private static function matchFlags(int $matchMode): string
    {
        return ($matchMode === 0) ? 'mu' : 'miu';
    }
}
