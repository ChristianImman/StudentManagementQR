<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class DateValue
{
    use ArrayEnabled;

    
    public static function fromString(null|array|string|int|bool|float $dateValue): array|string|float|int|DateTime
    {
        if (is_array($dateValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $dateValue);
        }

        
        if (is_string($dateValue) && preg_match('/\d/', $dateValue) !== 1) {
            return ExcelError::VALUE();
        }

        $dti = new DateTimeImmutable();
        $baseYear = SharedDateHelper::getExcelCalendar();
        $dateValue = trim((string) $dateValue, '"');
        
        $dateValue = (string) preg_replace('/(\d)(st|nd|rd|th)([ -\/])/Ui', '$1$3', $dateValue);
        
        $dateValue = str_replace(['/', '.', '-', '  '], ' ', $dateValue);

        $yearFound = false;
        $t1 = explode(' ', $dateValue);
        $t = '';
        foreach ($t1 as &$t) {
            if ((is_numeric($t)) && ($t > 31)) {
                if ($yearFound) {
                    return ExcelError::VALUE();
                }
                if ($t < 100) {
                    $t += 1900;
                }
                $yearFound = true;
            }
        }
        if (count($t1) === 1) {
            
            return ((!str_contains((string) $t, ':'))) ? ExcelError::Value() : 0.0;
        }
        unset($t);

        $dateValue = self::t1ToString($t1, $dti, $yearFound);

        $PHPDateArray = self::setUpArray($dateValue, $dti);

        return self::finalResults($PHPDateArray, $dti, $baseYear);
    }

    private static function t1ToString(array $t1, DateTimeImmutable $dti, bool $yearFound): string
    {
        if (count($t1) == 2) {
            
            if ($yearFound) {
                array_unshift($t1, 1);
            } else {
                if (is_numeric($t1[1]) && $t1[1] > 29) {
                    $t1[1] += 1900;
                    array_unshift($t1, 1);
                } else {
                    $t1[] = $dti->format('Y');
                }
            }
        }
        $dateValue = implode(' ', $t1);

        return $dateValue;
    }

    
    private static function setUpArray(string $dateValue, DateTimeImmutable $dti): array
    {
        $PHPDateArray = Helpers::dateParse($dateValue);
        if (!Helpers::dateParseSucceeded($PHPDateArray)) {
            
            
            
            $testVal1 = strtok($dateValue, '- ');
            $testVal2 = strtok('- ');
            $testVal3 = strtok('- ') ?: $dti->format('Y');
            Helpers::adjustYear((string) $testVal1, (string) $testVal2, $testVal3);
            $PHPDateArray = Helpers::dateParse($testVal1 . '-' . $testVal2 . '-' . $testVal3);
            if (!Helpers::dateParseSucceeded($PHPDateArray)) {
                $PHPDateArray = Helpers::dateParse($testVal2 . '-' . $testVal1 . '-' . $testVal3);
            }
        }

        return $PHPDateArray;
    }

    
    private static function finalResults(array $PHPDateArray, DateTimeImmutable $dti, int $baseYear): string|float|int|DateTime
    {
        $retValue = ExcelError::Value();
        if (Helpers::dateParseSucceeded($PHPDateArray)) {
            
            Helpers::replaceIfEmpty($PHPDateArray['year'], $dti->format('Y'));
            if ($PHPDateArray['year'] < $baseYear) {
                return ExcelError::VALUE();
            }
            Helpers::replaceIfEmpty($PHPDateArray['month'], $dti->format('m'));
            Helpers::replaceIfEmpty($PHPDateArray['day'], $dti->format('d'));
            $PHPDateArray['hour'] = 0;
            $PHPDateArray['minute'] = 0;
            $PHPDateArray['second'] = 0;
            $month = self::getInt($PHPDateArray, 'month');
            $day = self::getInt($PHPDateArray, 'day');
            $year = self::getInt($PHPDateArray, 'year');
            if (!checkdate($month, $day, $year)) {
                return ($year === 1900 && $month === 2 && $day === 29) ? Helpers::returnIn3FormatsFloat(60.0) : ExcelError::VALUE();
            }
            $retValue = Helpers::returnIn3FormatsArray($PHPDateArray, true);
        }

        return $retValue;
    }

    private static function getInt(array $array, string $index): int
    {
        return (array_key_exists($index, $array) && is_numeric($array[$index])) ? (int) $array[$index] : 0;
    }
}
