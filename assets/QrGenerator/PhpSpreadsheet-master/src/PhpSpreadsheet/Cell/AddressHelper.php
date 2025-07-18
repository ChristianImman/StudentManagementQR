<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Exception;

class AddressHelper
{
    public const R1C1_COORDINATE_REGEX = '/(R((?:\[-?\d*\])|(?:\d*))?)(C((?:\[-?\d*\])|(?:\d*))?)/i';

    
    public static function getRowAndColumnChars(): array
    {
        $rowChar = 'R';
        $colChar = 'C';
        if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_EXCEL) {
            $rowColChars = Calculation::localeFunc('*RC');
            if (mb_strlen($rowColChars) === 2) {
                $rowChar = mb_substr($rowColChars, 0, 1);
                $colChar = mb_substr($rowColChars, 1, 1);
            }
        }

        return [$rowChar, $colChar];
    }

    
    public static function convertToA1(
        string $address,
        int $currentRowNumber = 1,
        int $currentColumnNumber = 1,
        bool $useLocale = true
    ): string {
        [$rowChar, $colChar] = $useLocale ? self::getRowAndColumnChars() : ['R', 'C'];
        $regex = '/^(' . $rowChar . '(\[?[-+]?\d*\]?))(' . $colChar . '(\[?[-+]?\d*\]?))$/i';
        $validityCheck = preg_match($regex, $address, $cellReference);

        if (empty($validityCheck)) {
            throw new Exception('Invalid R1C1-format Cell Reference');
        }

        $rowReference = $cellReference[2];
        
        if ($rowReference === '') {
            $rowReference = (string) $currentRowNumber;
        }
        
        if ($rowReference[0] === '[') {
            $rowReference = $currentRowNumber + (int) trim($rowReference, '[]');
        }
        $columnReference = $cellReference[4];
        
        if ($columnReference === '') {
            $columnReference = (string) $currentColumnNumber;
        }
        
        if (is_string($columnReference) && $columnReference[0] === '[') { 
            $columnReference = $currentColumnNumber + (int) trim($columnReference, '[]');
        }
        $columnReference = (int) $columnReference;

        if ($columnReference <= 0 || $rowReference <= 0) {
            throw new Exception('Invalid R1C1-format Cell Reference, Value out of range');
        }
        $A1CellReference = Coordinate::stringFromColumnIndex($columnReference) . $rowReference;

        return $A1CellReference;
    }

    protected static function convertSpreadsheetMLFormula(string $formula): string
    {
        $formula = substr($formula, 3);
        $temp = explode('"', $formula);
        $key = false;
        foreach ($temp as &$value) {
            
            $key = $key === false;
            if ($key) {
                $value = str_replace(['[.', ':.', ']'], ['', ':', ''], $value);
            }
        }
        unset($value);

        return implode('"', $temp);
    }

    
    public static function convertFormulaToA1(
        string $formula,
        int $currentRowNumber = 1,
        int $currentColumnNumber = 1
    ): string {
        if (str_starts_with($formula, 'of:')) {
            
            return self::convertSpreadsheetMLFormula($formula);
        }

        
        $temp = explode('"', $formula);
        $key = false;
        foreach ($temp as &$value) {
            
            $key = $key === false;
            if ($key) {
                preg_match_all(self::R1C1_COORDINATE_REGEX, $value, $cellReferences, PREG_SET_ORDER + PREG_OFFSET_CAPTURE);
                
                
                
                $cellReferences = array_reverse($cellReferences);
                
                
                foreach ($cellReferences as $cellReference) {
                    $A1CellReference = self::convertToA1($cellReference[0][0], $currentRowNumber, $currentColumnNumber, false);
                    $value = substr_replace($value, $A1CellReference, $cellReference[0][1], strlen($cellReference[0][0]));
                }
            }
        }
        unset($value);

        
        return implode('"', $temp);
    }

    
    public static function convertToR1C1(
        string $address,
        ?int $currentRowNumber = null,
        ?int $currentColumnNumber = null
    ): string {
        if (1 !== preg_match(Coordinate::A1_COORDINATE_REGEX, $address, $cellReference)) {
            throw new Exception('Invalid A1-format Cell Reference');
        }

        if ($cellReference['col'][0] === '$') {
            
            $currentColumnNumber = null;
        }
        $columnId = Coordinate::columnIndexFromString(ltrim($cellReference['col'], '$'));

        if ($cellReference['row'][0] === '$') {
            
            $currentRowNumber = null;
        }
        $rowId = (int) ltrim($cellReference['row'], '$');

        if ($currentRowNumber !== null) {
            if ($rowId === $currentRowNumber) {
                $rowId = '';
            } else {
                $rowId = '[' . ($rowId - $currentRowNumber) . ']';
            }
        }

        if ($currentColumnNumber !== null) {
            if ($columnId === $currentColumnNumber) {
                $columnId = '';
            } else {
                $columnId = '[' . ($columnId - $currentColumnNumber) . ']';
            }
        }

        $R1C1Address = "R{$rowId}C{$columnId}";

        return $R1C1Address;
    }
}
