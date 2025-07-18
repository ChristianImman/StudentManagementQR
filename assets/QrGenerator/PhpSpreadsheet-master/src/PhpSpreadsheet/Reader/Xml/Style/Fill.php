<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xml\Style;

use PhpOffice\PhpSpreadsheet\Style\Fill as FillStyles;
use SimpleXMLElement;

class Fill extends StyleBase
{
    public const FILL_MAPPINGS = [
        'fillType' => [
            'solid' => FillStyles::FILL_SOLID,
            'gray75' => FillStyles::FILL_PATTERN_DARKGRAY,
            'gray50' => FillStyles::FILL_PATTERN_MEDIUMGRAY,
            'gray25' => FillStyles::FILL_PATTERN_LIGHTGRAY,
            'gray125' => FillStyles::FILL_PATTERN_GRAY125,
            'gray0625' => FillStyles::FILL_PATTERN_GRAY0625,
            'horzstripe' => FillStyles::FILL_PATTERN_DARKHORIZONTAL, 
            'vertstripe' => FillStyles::FILL_PATTERN_DARKVERTICAL, 
            'reversediagstripe' => FillStyles::FILL_PATTERN_DARKUP, 
            'diagstripe' => FillStyles::FILL_PATTERN_DARKDOWN, 
            'diagcross' => FillStyles::FILL_PATTERN_DARKGRID, 
            'thickdiagcross' => FillStyles::FILL_PATTERN_DARKTRELLIS, 
            'thinhorzstripe' => FillStyles::FILL_PATTERN_LIGHTHORIZONTAL,
            'thinvertstripe' => FillStyles::FILL_PATTERN_LIGHTVERTICAL,
            'thinreversediagstripe' => FillStyles::FILL_PATTERN_LIGHTUP,
            'thindiagstripe' => FillStyles::FILL_PATTERN_LIGHTDOWN,
            'thinhorzcross' => FillStyles::FILL_PATTERN_LIGHTGRID, 
            'thindiagcross' => FillStyles::FILL_PATTERN_LIGHTTRELLIS, 
        ],
    ];

    
    public function parseStyle(SimpleXMLElement $styleAttributes): array
    {
        $style = [];

        foreach ($styleAttributes as $styleAttributeKey => $styleAttributeValuex) {
            $styleAttributeValue = (string) $styleAttributeValuex;
            switch ($styleAttributeKey) {
                case 'Color':
                    $style['fill']['endColor']['rgb'] = substr($styleAttributeValue, 1);
                    $style['fill']['startColor']['rgb'] = substr($styleAttributeValue, 1);

                    break;
                case 'PatternColor':
                    $style['fill']['startColor']['rgb'] = substr($styleAttributeValue, 1);

                    break;
                case 'Pattern':
                    $lcStyleAttributeValue = strtolower((string) $styleAttributeValue);
                    $style['fill']['fillType']
                        = self::FILL_MAPPINGS['fillType'][$lcStyleAttributeValue] ?? FillStyles::FILL_NONE;

                    break;
            }
        }

        return $style;
    }
}
