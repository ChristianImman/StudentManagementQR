<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\FormattedNumber;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AdvancedValueBinder extends DefaultValueBinder implements IValueBinder
{
    
    public function bindValue(Cell $cell, mixed $value = null): bool
    {
        if ($value === null) {
            return parent::bindValue($cell, $value);
        } elseif (is_string($value)) {
            
            $value = StringHelper::sanitizeUTF8($value);
        }

        
        $dataType = parent::dataTypeForValue($value);

        
        if ($dataType === DataType::TYPE_STRING && is_string($value)) {
            
            if (StringHelper::strToUpper($value) === Calculation::getTRUE()) {
                $cell->setValueExplicit(true, DataType::TYPE_BOOL);

                return true;
            } elseif (StringHelper::strToUpper($value) === Calculation::getFALSE()) {
                $cell->setValueExplicit(false, DataType::TYPE_BOOL);

                return true;
            }

            
            if (preg_match('~^([+-]?)\s*(\d+)\s*/\s*(\d+)$~', $value, $matches)) {
                return $this->setProperFraction($matches, $cell);
            } elseif (preg_match('~^([+-]?)(\d+)\s+(\d+)\s*/\s*(\d+)$~', $value, $matches)) {
                return $this->setImproperFraction($matches, $cell);
            }

            $decimalSeparatorNoPreg = StringHelper::getDecimalSeparator();
            $decimalSeparator = preg_quote($decimalSeparatorNoPreg, '/');
            $thousandsSeparator = preg_quote(StringHelper::getThousandsSeparator(), '/');

            
            if (preg_match('/^\-?\d*' . $decimalSeparator . '?\d*\s?\%$/', (string) preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', $value))) {
                return $this->setPercentage((string) preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', $value), $cell);
            }

            
            if (preg_match(FormattedNumber::currencyMatcherRegexp(), (string) preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', $value), $matches, PREG_UNMATCHED_AS_NULL)) {
                
                $sign = ($matches['PrefixedSign'] ?? $matches['PrefixedSign2'] ?? $matches['PostfixedSign']) ?? null;
                $currencyCode = $matches['PrefixedCurrency'] ?? $matches['PostfixedCurrency'] ?? '';
                
                $temp = str_replace([$decimalSeparatorNoPreg, $currencyCode, ' ', '-'], ['.', '', '', ''], (string) preg_replace('/(\d)' . $thousandsSeparator . '(\d)/u', '$1$2', $value));
                $value = (float) ($sign . trim($temp));

                return $this->setCurrency($value, $cell, $currencyCode);
            }

            
            if (preg_match('/^(\d|[0-1]\d|2[0-3]):[0-5]\d$/', $value)) {
                return $this->setTimeHoursMinutes($value, $cell);
            }

            
            if (preg_match('/^(\d|[0-1]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
                return $this->setTimeHoursMinutesSeconds($value, $cell);
            }

            
            if (($d = Date::stringToExcel($value)) !== false) {
                
                $cell->setValueExplicit($d, DataType::TYPE_NUMERIC);
                
                if (str_contains($value, ':')) {
                    $formatCode = 'yyyy-mm-dd h:mm';
                } else {
                    $formatCode = 'yyyy-mm-dd';
                }
                $cell->getWorksheet()->getStyle($cell->getCoordinate())
                    ->getNumberFormat()->setFormatCode($formatCode);

                return true;
            }

            
            if (str_contains($value, "\n")) {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
                
                $cell->getWorksheet()->getStyle($cell->getCoordinate())
                    ->getAlignment()->setWrapText(true);

                return true;
            }
        }

        
        return parent::bindValue($cell, $value);
    }

    
    protected function setImproperFraction(array $matches, Cell $cell): bool
    {
        
        $value = $matches[2] + ($matches[3] / $matches[4]);
        if ($matches[1] === '-') {
            $value = 0 - $value;
        }
        $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

        
        $dividend = str_repeat('?', strlen($matches[3]));
        $divisor = str_repeat('?', strlen($matches[4]));
        $fractionMask = "
        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode($fractionMask);

        return true;
    }

    
    protected function setProperFraction(array $matches, Cell $cell): bool
    {
        
        $value = $matches[2] / $matches[3];
        if ($matches[1] === '-') {
            $value = 0 - $value;
        }
        $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

        
        $dividend = str_repeat('?', strlen($matches[2]));
        $divisor = str_repeat('?', strlen($matches[3]));
        $fractionMask = "{$dividend}/{$divisor}";
        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode($fractionMask);

        return true;
    }

    protected function setPercentage(string $value, Cell $cell): bool
    {
        
        $value = ((float) str_replace('%', '', $value)) / 100;
        $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

        return true;
    }

    protected function setCurrency(float $value, Cell $cell, string $currencyCode): bool
    {
        $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode(
                str_replace('$', '[$' . $currencyCode . ']', NumberFormat::FORMAT_CURRENCY_USD)
            );

        return true;
    }

    protected function setTimeHoursMinutes(string $value, Cell $cell): bool
    {
        
        [$hours, $minutes] = explode(':', $value);
        $hours = (int) $hours;
        $minutes = (int) $minutes;
        $days = ($hours / 24) + ($minutes / 1440);
        $cell->setValueExplicit($days, DataType::TYPE_NUMERIC);

        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);

        return true;
    }

    protected function setTimeHoursMinutesSeconds(string $value, Cell $cell): bool
    {
        
        [$hours, $minutes, $seconds] = explode(':', $value);
        $hours = (int) $hours;
        $minutes = (int) $minutes;
        $seconds = (int) $seconds;
        $days = ($hours / 24) + ($minutes / 1440) + ($seconds / 86400);
        $cell->setValueExplicit($days, DataType::TYPE_NUMERIC);

        
        $cell->getWorksheet()->getStyle($cell->getCoordinate())
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME4);

        return true;
    }
}
