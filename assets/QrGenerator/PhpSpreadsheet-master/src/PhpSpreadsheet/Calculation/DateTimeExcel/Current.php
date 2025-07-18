<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Current
{
    
    public static function today(): DateTime|float|int|string
    {
        $dti = new DateTimeImmutable();
        $dateArray = Helpers::dateParse($dti->format('c'));

        return Helpers::dateParseSucceeded($dateArray) ? Helpers::returnIn3FormatsArray($dateArray, true) : ExcelError::VALUE();
    }

    
    public static function now(): DateTime|float|int|string
    {
        $dti = new DateTimeImmutable();
        $dateArray = Helpers::dateParse($dti->format('c'));

        return Helpers::dateParseSucceeded($dateArray) ? Helpers::returnIn3FormatsArray($dateArray) : ExcelError::VALUE();
    }
}
