<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;

abstract class AggregateBase
{
    
    protected static function testAcceptedBoolean(mixed $arg, mixed $k): mixed
    {
        if (!is_bool($arg)) {
            return $arg;
        }
        if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_GNUMERIC) {
            return $arg;
        }
        if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
            return (int) $arg;
        }
        if (!Functions::isCellValue($k)) {
            return (int) $arg;
        }
        

        return $arg;
    }

    protected static function isAcceptedCountable(mixed $arg, mixed $k, bool $countNull = false): bool
    {
        if ($countNull && $arg === null && !Functions::isCellValue($k) && Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_GNUMERIC) {
            return true;
        }
        if (!is_numeric($arg)) {
            return false;
        }
        if (!is_string($arg)) {
            return true;
        }
        if (!Functions::isCellValue($k) && Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
            return true;
        }
        if (!Functions::isCellValue($k) && Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_GNUMERIC) {
            return true;
        }

        return false;
    }
}
