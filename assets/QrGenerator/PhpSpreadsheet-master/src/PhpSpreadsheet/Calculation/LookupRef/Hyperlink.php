<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Hyperlink
{
    
    public static function set(mixed $linkURL = '', mixed $displayName = null, ?Cell $cell = null): string
    {
        $linkURL = ($linkURL === null) ? '' : StringHelper::convertToString(Functions::flattenSingleValue($linkURL));
        $displayName = ($displayName === null) ? '' : Functions::flattenSingleValue($displayName);

        if ((!is_object($cell)) || (trim($linkURL) == '')) {
            return ExcelError::REF();
        }

        if (is_object($displayName)) {
            $displayName = $linkURL;
        }
        $displayName = StringHelper::convertToString($displayName);
        if (trim($displayName) === '') {
            $displayName = $linkURL;
        }

        $cell->getHyperlink()
            ->setUrl($linkURL);
        $cell->getHyperlink()->setTooltip($displayName);

        return $displayName;
    }
}
