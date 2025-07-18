<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CellDataValidation
{
    
    protected static array $validationTypeMap = [
        DataValidation::TYPE_NONE => 0x00,
        DataValidation::TYPE_WHOLE => 0x01,
        DataValidation::TYPE_DECIMAL => 0x02,
        DataValidation::TYPE_LIST => 0x03,
        DataValidation::TYPE_DATE => 0x04,
        DataValidation::TYPE_TIME => 0x05,
        DataValidation::TYPE_TEXTLENGTH => 0x06,
        DataValidation::TYPE_CUSTOM => 0x07,
    ];

    
    protected static array $errorStyleMap = [
        DataValidation::STYLE_STOP => 0x00,
        DataValidation::STYLE_WARNING => 0x01,
        DataValidation::STYLE_INFORMATION => 0x02,
    ];

    
    protected static array $operatorMap = [
        DataValidation::OPERATOR_BETWEEN => 0x00,
        DataValidation::OPERATOR_NOTBETWEEN => 0x01,
        DataValidation::OPERATOR_EQUAL => 0x02,
        DataValidation::OPERATOR_NOTEQUAL => 0x03,
        DataValidation::OPERATOR_GREATERTHAN => 0x04,
        DataValidation::OPERATOR_LESSTHAN => 0x05,
        DataValidation::OPERATOR_GREATERTHANOREQUAL => 0x06,
        DataValidation::OPERATOR_LESSTHANOREQUAL => 0x07,
    ];

    public static function type(DataValidation $dataValidation): int
    {
        $validationType = $dataValidation->getType();

        if (array_key_exists($validationType, self::$validationTypeMap)) {
            return self::$validationTypeMap[$validationType];
        }

        return self::$validationTypeMap[DataValidation::TYPE_NONE];
    }

    public static function errorStyle(DataValidation $dataValidation): int
    {
        $errorStyle = $dataValidation->getErrorStyle();

        if (array_key_exists($errorStyle, self::$errorStyleMap)) {
            return self::$errorStyleMap[$errorStyle];
        }

        return self::$errorStyleMap[DataValidation::STYLE_STOP];
    }

    public static function operator(DataValidation $dataValidation): int
    {
        $operator = $dataValidation->getOperator();

        if (array_key_exists($operator, self::$operatorMap)) {
            return self::$operatorMap[$operator];
        }

        return self::$operatorMap[DataValidation::OPERATOR_BETWEEN];
    }
}
