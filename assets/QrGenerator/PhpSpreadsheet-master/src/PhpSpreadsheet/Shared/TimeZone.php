<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class TimeZone
{
    
    protected static string $timezone = 'UTC';

    
    private static function validateTimeZone(string $timezoneName): bool
    {
        return in_array($timezoneName, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
    }

    
    public static function setTimeZone(string $timezoneName): bool
    {
        if (self::validateTimeZone($timezoneName)) {
            self::$timezone = $timezoneName;

            return true;
        }

        return false;
    }

    
    public static function getTimeZone(): string
    {
        return self::$timezone;
    }

    
    public static function getTimeZoneAdjustment(?string $timezoneName, $timestamp): int
    {
        $timezoneName = $timezoneName ?? self::$timezone;
        $dtobj = Date::dateTimeFromTimestamp("$timestamp");
        if (!self::validateTimeZone($timezoneName)) {
            throw new PhpSpreadsheetException("Invalid timezone $timezoneName");
        }
        $dtobj->setTimeZone(new DateTimeZone($timezoneName));

        return $dtobj->getOffset();
    }
}
