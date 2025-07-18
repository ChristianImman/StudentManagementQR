<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Worksheet as XlsWorksheet;

class DataValidationHelper extends Xls
{
    
    private static array $types = [
        0x00 => DataValidation::TYPE_NONE,
        0x01 => DataValidation::TYPE_WHOLE,
        0x02 => DataValidation::TYPE_DECIMAL,
        0x03 => DataValidation::TYPE_LIST,
        0x04 => DataValidation::TYPE_DATE,
        0x05 => DataValidation::TYPE_TIME,
        0x06 => DataValidation::TYPE_TEXTLENGTH,
        0x07 => DataValidation::TYPE_CUSTOM,
    ];

    
    private static array $errorStyles = [
        0x00 => DataValidation::STYLE_STOP,
        0x01 => DataValidation::STYLE_WARNING,
        0x02 => DataValidation::STYLE_INFORMATION,
    ];

    
    private static array $operators = [
        0x00 => DataValidation::OPERATOR_BETWEEN,
        0x01 => DataValidation::OPERATOR_NOTBETWEEN,
        0x02 => DataValidation::OPERATOR_EQUAL,
        0x03 => DataValidation::OPERATOR_NOTEQUAL,
        0x04 => DataValidation::OPERATOR_GREATERTHAN,
        0x05 => DataValidation::OPERATOR_LESSTHAN,
        0x06 => DataValidation::OPERATOR_GREATERTHANOREQUAL,
        0x07 => DataValidation::OPERATOR_LESSTHANOREQUAL,
    ];

    public static function type(int $type): ?string
    {
        return self::$types[$type] ?? null;
    }

    public static function errorStyle(int $errorStyle): ?string
    {
        return self::$errorStyles[$errorStyle] ?? null;
    }

    public static function operator(int $operator): ?string
    {
        return self::$operators[$operator] ?? null;
    }

    
    protected function readDataValidation2(Xls $xls): void
    {
        $length = self::getUInt2d($xls->data, $xls->pos + 2);
        $recordData = $xls->readRecordData($xls->data, $xls->pos + 4, $length);

        
        $xls->pos += 4 + $length;

        if ($xls->readDataOnly) {
            return;
        }

        
        $options = self::getInt4d($recordData, 0);

        
        $type = (0x0000000F & $options) >> 0;
        $type = self::type($type);

        
        $errorStyle = (0x00000070 & $options) >> 4;
        $errorStyle = self::errorStyle($errorStyle);

        
        
        

        
        $allowBlank = (0x00000100 & $options) >> 8;

        
        $suppressDropDown = (0x00000200 & $options) >> 9;

        
        $showInputMessage = (0x00040000 & $options) >> 18;

        
        $showErrorMessage = (0x00080000 & $options) >> 19;

        
        $operator = (0x00F00000 & $options) >> 20;
        $operator = self::operator($operator);

        if ($type === null || $errorStyle === null || $operator === null) {
            return;
        }

        
        $offset = 4;
        $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $promptTitle = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

        
        $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $errorTitle = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

        
        $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $prompt = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

        
        $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $error = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

        
        $sz1 = self::getUInt2d($recordData, $offset);
        $offset += 2;

        
        $offset += 2;

        
        $formula1 = substr($recordData, $offset, $sz1);
        $formula1 = pack('v', $sz1) . $formula1; 

        try {
            $formula1 = $xls->getFormulaFromStructure($formula1);

            
            if ($type == DataValidation::TYPE_LIST) {
                $formula1 = str_replace(chr(0), ',', $formula1);
            }
        } catch (PhpSpreadsheetException $e) {
            return;
        }
        $offset += $sz1;

        
        $sz2 = self::getUInt2d($recordData, $offset);
        $offset += 2;

        
        $offset += 2;

        
        $formula2 = substr($recordData, $offset, $sz2);
        $formula2 = pack('v', $sz2) . $formula2; 

        try {
            $formula2 = $xls->getFormulaFromStructure($formula2);
        } catch (PhpSpreadsheetException) {
            return;
        }
        $offset += $sz2;

        
        $cellRangeAddressList = Biff8::readBIFF8CellRangeAddressList(substr($recordData, $offset));
        $cellRangeAddresses = $cellRangeAddressList['cellRangeAddresses'];
        $maxRow = (string) AddressRange::MAX_ROW;
        $maxCol = AddressRange::MAX_COLUMN;
        $maxXlsRow = (string) XlsWorksheet::MAX_XLS_ROW;
        $maxXlsColumnString = (string) XlsWorksheet::MAX_XLS_COLUMN_STRING;

        foreach ($cellRangeAddresses as $cellRange) {
            $cellRange = preg_replace(
                [
                    "/([a-z]+)1:([a-z]+)$maxXlsRow/i",
                    "/([a-z]+\\d+):([a-z]+)$maxXlsRow/i",
                    "/A(\\d+):$maxXlsColumnString(\\d+)/i",
                    "/([a-z]+\\d+):$maxXlsColumnString(\\d+)/i",
                ],
                [
                    '$1:$2',
                    '$1:${2}' . $maxRow,
                    '$1:$2',
                    '$1:' . $maxCol . '$2',
                ],
                $cellRange
            ) ?? $cellRange;
            $objValidation = new DataValidation();
            $objValidation->setType($type);
            $objValidation->setErrorStyle($errorStyle);
            $objValidation->setAllowBlank((bool) $allowBlank);
            $objValidation->setShowInputMessage((bool) $showInputMessage);
            $objValidation->setShowErrorMessage((bool) $showErrorMessage);
            $objValidation->setShowDropDown(!$suppressDropDown);
            $objValidation->setOperator($operator);
            $objValidation->setErrorTitle($errorTitle);
            $objValidation->setError($error);
            $objValidation->setPromptTitle($promptTitle);
            $objValidation->setPrompt($prompt);
            $objValidation->setFormula1($formula1);
            $objValidation->setFormula2($formula2);
            $xls->phpSheet->setDataValidation($cellRange, $objValidation);
        }
    }
}
