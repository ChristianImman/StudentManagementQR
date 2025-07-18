<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Date
{
    
    const CALENDAR_WINDOWS_1900 = 1900; 
    const CALENDAR_MAC_1904 = 1904; 

    
    public static array $monthNames = [
        'Jan' => 'January',
        'Feb' => 'February',
        'Mar' => 'March',
        'Apr' => 'April',
        'May' => 'May',
        'Jun' => 'June',
        'Jul' => 'July',
        'Aug' => 'August',
        'Sep' => 'September',
        'Oct' => 'October',
        'Nov' => 'November',
        'Dec' => 'December',
    ];

    
    public static array $numberSuffixes = [
        'st',
        'nd',
        'rd',
        'th',
    ];

    
    protected static int $excelCalendar = self::CALENDAR_WINDOWS_1900;

    
    protected static ?DateTimeZone $defaultTimeZone = null;

    
    public static function setExcelCalendar(?int $baseYear): bool
    {
        if (
            ($baseYear === self::CALENDAR_WINDOWS_1900)
            || ($baseYear === self::CALENDAR_MAC_1904)
        ) {
            self::$excelCalendar = $baseYear;

            return true;
        }

        return false;
    }

    
    public static function getExcelCalendar(): int
    {
        return self::$excelCalendar;
    }

    
    public static function setDefaultTimezone($timeZone): bool
    {
        try {
            $timeZone = self::validateTimeZone($timeZone);
            self::$defaultTimeZone = $timeZone;
            $retval = true;
        } catch (PhpSpreadsheetException) {
            $retval = false;
        }

        return $retval;
    }

    
    public static function getDefaultTimezone(): DateTimeZone
    {
        return self::$defaultTimeZone ?? new DateTimeZone('UTC');
    }

    
    public static function getDefaultOrLocalTimezone(): DateTimeZone
    {
        return self::$defaultTimeZone ?? new DateTimeZone(date_default_timezone_get());
    }

    
    public static function getDefaultTimezoneOrNull(): ?DateTimeZone
    {
        return self::$defaultTimeZone;
    }

    
    private static function validateTimeZone($timeZone): ?DateTimeZone
    {
        if ($timeZone instanceof DateTimeZone || $timeZone === null) {
            return $timeZone;
        }
        if (in_array($timeZone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC))) {
            return new DateTimeZone($timeZone);
        }

        throw new PhpSpreadsheetException('Invalid timezone');
    }

    
    public static function convertIsoDate(mixed $value): float|int
    {
        if (!is_string($value)) {
            throw new Exception('Non-string value supplied for Iso Date conversion');
        }

        $date = new DateTime($value);
        $dateErrors = DateTime::getLastErrors();

        if (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) {
            throw new Exception("Invalid string $value supplied for datatype Date");
        }

        $newValue = self::PHPToExcel($date);
        if ($newValue === false) {
            throw new Exception("Invalid string $value supplied for datatype Date");
        }

        if (preg_match('/^\s*\d?\d:\d\d(:\d\d([.]\d+)?)?\s*(am|pm)?\s*$/i', $value) == 1) {
            $newValue = fmod($newValue, 1.0);
        }

        return $newValue;
    }

    
    public static function excelToDateTimeObject(float|int $excelTimestamp, null|DateTimeZone|string $timeZone = null): DateTime
    {
        $timeZone = ($timeZone === null) ? self::getDefaultTimezone() : self::validateTimeZone($timeZone);
        if (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_EXCEL) {
            if ($excelTimestamp < 1 && self::$excelCalendar === self::CALENDAR_WINDOWS_1900) {
                
                $baseDate = new DateTime('1970-01-01', $timeZone);
            } else {
                
                if (self::$excelCalendar == self::CALENDAR_WINDOWS_1900) {
                    
                    $baseDate = ($excelTimestamp < 60) ? new DateTime('1899-12-31', $timeZone) : new DateTime('1899-12-30', $timeZone);
                } else {
                    $baseDate = new DateTime('1904-01-01', $timeZone);
                }
            }
        } else {
            $baseDate = new DateTime('1899-12-30', $timeZone);
        }

        if (is_int($excelTimestamp)) {
            if ($excelTimestamp >= 0) {
                return $baseDate->modify("+ $excelTimestamp days");
            }

            return $baseDate->modify("$excelTimestamp days");
        }
        $days = floor($excelTimestamp);
        $partDay = $excelTimestamp - $days;
        $hms = 86400 * $partDay;
        $microseconds = (int) round(fmod($hms, 1) * 1000000);
        $hms = (int) floor($hms);
        $hours = intdiv($hms, 3600);
        $hms -= $hours * 3600;
        $minutes = intdiv($hms, 60);
        $seconds = $hms % 60;

        if ($days >= 0) {
            $days = '+' . $days;
        }
        $interval = $days . ' days';

        return $baseDate->modify($interval)
            ->setTime($hours, $minutes, $seconds, $microseconds);
    }

    
    public static function excelToTimestamp($excelTimestamp, $timeZone = null): int
    {
        $dto = self::excelToDateTimeObject($excelTimestamp, $timeZone);
        self::roundMicroseconds($dto);

        return (int) $dto->format('U');
    }

    
    public static function PHPToExcel(mixed $dateValue)
    {
        if ((is_object($dateValue)) && ($dateValue instanceof DateTimeInterface)) {
            return self::dateTimeToExcel($dateValue);
        } elseif (is_numeric($dateValue)) {
            return self::timestampToExcel($dateValue);
        } elseif (is_string($dateValue)) {
            return self::stringToExcel($dateValue);
        }

        return false;
    }

    
    public static function dateTimeToExcel(DateTimeInterface $dateValue): float
    {
        $seconds = (float) sprintf('%d.%06d', $dateValue->format('s'), $dateValue->format('u'));

        return self::formattedPHPToExcel(
            (int) $dateValue->format('Y'),
            (int) $dateValue->format('m'),
            (int) $dateValue->format('d'),
            (int) $dateValue->format('H'),
            (int) $dateValue->format('i'),
            $seconds
        );
    }

    
    public static function timestampToExcel($unixTimestamp): bool|float
    {
        if (!is_numeric($unixTimestamp)) {
            return false;
        }

        return self::dateTimeToExcel(new DateTime('@' . $unixTimestamp));
    }

    
    public static function formattedPHPToExcel(int $year, int $month, int $day, int $hours = 0, int $minutes = 0, float|int $seconds = 0): float
    {
        if (self::$excelCalendar == self::CALENDAR_WINDOWS_1900) {
            
            
            
            
            $excel1900isLeapYear = true;
            if (($year == 1900) && ($month <= 2)) {
                $excel1900isLeapYear = false;
            }
            $myexcelBaseDate = 2415020;
        } else {
            $myexcelBaseDate = 2416481;
            $excel1900isLeapYear = false;
        }

        
        if ($month > 2) {
            $month -= 3;
        } else {
            $month += 9;
            --$year;
        }

        
        $century = (int) substr((string) $year, 0, 2);
        $decade = (int) substr((string) $year, 2, 2);
        $excelDate = floor((146097 * $century) / 4) + floor((1461 * $decade) / 4) + floor((153 * $month + 2) / 5) + $day + 1721119 - $myexcelBaseDate + $excel1900isLeapYear;

        $excelTime = (($hours * 3600) + ($minutes * 60) + $seconds) / 86400;

        return (float) $excelDate + $excelTime;
    }

    
    public static function isDateTime(Cell $cell, mixed $value = null, bool $dateWithoutTimeOkay = true): bool
    {
        $result = false;
        $worksheet = $cell->getWorksheetOrNull();
        $spreadsheet = ($worksheet === null) ? null : $worksheet->getParent();
        if ($worksheet !== null && $spreadsheet !== null) {
            $index = $spreadsheet->getActiveSheetIndex();
            $selected = $worksheet->getSelectedCells();

            try {
                $result = is_numeric($value ?? $cell->getCalculatedValue())
                    && self::isDateTimeFormat(
                        $worksheet->getStyle(
                            $cell->getCoordinate()
                        )->getNumberFormat(),
                        $dateWithoutTimeOkay
                    );
            } catch (Exception) {
                
            }
            $worksheet->setSelectedCells($selected);
            $spreadsheet->setActiveSheetIndex($index);
        }

        return $result;
    }

    
    public static function isDateTimeFormat(NumberFormat $excelFormatCode, bool $dateWithoutTimeOkay = true): bool
    {
        return self::isDateTimeFormatCode((string) $excelFormatCode->getFormatCode(), $dateWithoutTimeOkay);
    }

    private const POSSIBLE_DATETIME_FORMAT_CHARACTERS = 'eymdHs';
    private const POSSIBLE_TIME_FORMAT_CHARACTERS = 'Hs'; 

    
    public static function isDateTimeFormatCode(string $excelFormatCode, bool $dateWithoutTimeOkay = true): bool
    {
        if (strtolower($excelFormatCode) === strtolower(NumberFormat::FORMAT_GENERAL)) {
            
            return false;
        }
        if (preg_match('/[0
            
            return false;
        }

        
        $excelFormatCode = (string) NumberFormat::convertSystemFormats($excelFormatCode);
        if (in_array($excelFormatCode, NumberFormat::DATE_TIME_OR_DATETIME_ARRAY, true)) {
            return $dateWithoutTimeOkay || in_array($excelFormatCode, NumberFormat::TIME_OR_DATETIME_ARRAY);
        }

        
        if ((str_starts_with($excelFormatCode, '_')) || (str_starts_with($excelFormatCode, '0 '))) {
            return false;
        }
        
        
        if (str_contains($excelFormatCode, '-00000')) {
            return false;
        }
        $possibleFormatCharacters = $dateWithoutTimeOkay ? self::POSSIBLE_DATETIME_FORMAT_CHARACTERS : self::POSSIBLE_TIME_FORMAT_CHARACTERS;
        
        if (preg_match('/(^|\])[^\[]*[' . $possibleFormatCharacters . ']/i', $excelFormatCode)) {
            
            
            if (str_contains($excelFormatCode, '"')) {
                $segMatcher = false;
                foreach (explode('"', $excelFormatCode) as $subVal) {
                    
                    $segMatcher = $segMatcher === false;
                    if (
                        $segMatcher
                        && (preg_match('/(^|\])[^\[]*[' . $possibleFormatCharacters . ']/i', $subVal))
                    ) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        }

        
        return false;
    }

    
    public static function stringToExcel(string $dateValue): bool|float
    {
        if (strlen($dateValue) < 2) {
            return false;
        }
        if (!preg_match('/^(\d{1,4}[ \.\/\-][A-Z]{3,9}([ \.\/\-]\d{1,4})?|[A-Z]{3,9}[ \.\/\-]\d{1,4}([ \.\/\-]\d{1,4})?|\d{1,4}[ \.\/\-]\d{1,4}([ \.\/\-]\d{1,4})?)( \d{1,2}:\d{1,2}(:\d{1,2})?)?$/iu', $dateValue)) {
            return false;
        }

        $dateValueNew = DateTimeExcel\DateValue::fromString($dateValue);

        if (!is_float($dateValueNew)) {
            return false;
        }

        if (str_contains($dateValue, ':')) {
            $timeValue = DateTimeExcel\TimeValue::fromString($dateValue);
            if (!is_float($timeValue)) {
                return false;
            }
            $dateValueNew += $timeValue;
        }

        return $dateValueNew;
    }

    
    public static function monthStringToNumber(string $monthName)
    {
        $monthIndex = 1;
        foreach (self::$monthNames as $shortMonthName => $longMonthName) {
            if (($monthName === $longMonthName) || ($monthName === $shortMonthName)) {
                return $monthIndex;
            }
            ++$monthIndex;
        }

        return $monthName;
    }

    
    public static function dayStringToNumber(string $day)
    {
        $strippedDayValue = (str_replace(self::$numberSuffixes, '', $day));
        if (is_numeric($strippedDayValue)) {
            return (int) $strippedDayValue;
        }

        return $day;
    }

    public static function dateTimeFromTimestamp(string $date, ?DateTimeZone $timeZone = null): DateTime
    {
        $dtobj = DateTime::createFromFormat('U', $date) ?: new DateTime();
        $dtobj->setTimeZone($timeZone ?? self::getDefaultOrLocalTimezone());

        return $dtobj;
    }

    public static function formattedDateTimeFromTimestamp(string $date, string $format, ?DateTimeZone $timeZone = null): string
    {
        $dtobj = self::dateTimeFromTimestamp($date, $timeZone);

        return $dtobj->format($format);
    }

    
    public static function roundMicroseconds(DateTime $dti): void
    {
        $microseconds = (int) $dti->format('u');
        $rounded = (int) round($microseconds, -6);
        $modify = $rounded - $microseconds;
        if ($modify !== 0) {
            $dti->modify(($modify > 0 ? '+' : '') . $modify . ' microseconds');
        }
    }
}
