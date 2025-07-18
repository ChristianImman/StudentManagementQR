<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use Composer\Pcre\Preg;
use Datetime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class TimeValue
{
    use ArrayEnabled;

    private const EXTRACT_TIME = '/\b'
        . '(\d+)' 
        . '(:' 
        . '(\d+' 
        . '(:\d+' 
        . '([.]\d+)?' 
        . ')?' 
        . ')' 
        
        . '(\s*(a|p))?' 
        . ')' 
        . '/i';

    
    public static function fromString(null|array|string|int|bool|float $timeValue): array|string|Datetime|int|float
    {
        if (is_array($timeValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $timeValue);
        }

        
        if (is_string($timeValue) && !Preg::isMatch('/\d/', $timeValue)) {
            return ExcelError::VALUE();
        }

        $timeValue = trim((string) $timeValue, '"');
        if (Preg::isMatch(self::EXTRACT_TIME, $timeValue, $matches)) {
            if (empty($matches[6])) { 
                $hour = (int) $matches[0];
                $timeValue = ($hour % 24) . $matches[2];
            } elseif ($matches[6] === $matches[7]) { 
                return ExcelError::VALUE();
            } else {
                $timeValue = $matches[0] . 'm';
            }
        }

        $PHPDateArray = Helpers::dateParse($timeValue);
        $retValue = ExcelError::VALUE();
        if (Helpers::dateParseSucceeded($PHPDateArray)) {
            $hour = $PHPDateArray['hour'];
            $minute = $PHPDateArray['minute'];
            $second = $PHPDateArray['second'];
            
            $excelDateValue = SharedDateHelper::formattedPHPToExcel(1900, 1, 1, $hour, $minute, $second) - 1;

            $retType = Functions::getReturnDateType();
            if ($retType === Functions::RETURNDATE_EXCEL) {
                $retValue = (float) $excelDateValue;
            } elseif ($retType === Functions::RETURNDATE_UNIX_TIMESTAMP) {
                $retValue = (int) SharedDateHelper::excelToTimestamp($excelDateValue + 25569) - 3600;
            } else {
                $retValue = new Datetime('1900-01-01 ' . $PHPDateArray['hour'] . ':' . $PHPDateArray['minute'] . ':' . $PHPDateArray['second']);
            }
        }

        return $retValue;
    }
}
