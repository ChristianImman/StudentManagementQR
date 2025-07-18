<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableDxfsStyle;
use SimpleXMLElement;
use stdClass;

class Styles extends BaseParserClass
{
    
    private ?Theme $theme = null;

    private array $workbookPalette = [];

    private array $styles = [];

    private array $cellStyles = [];

    private SimpleXMLElement $styleXml;

    private string $namespace = '';

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function setWorkbookPalette(array $palette): void
    {
        $this->workbookPalette = $palette;
    }

    private function getStyleAttributes(SimpleXMLElement $value): SimpleXMLElement
    {
        $attr = $value->attributes('');
        if ($attr === null || count($attr) === 0) {
            $attr = $value->attributes($this->namespace);
        }

        return Xlsx::testSimpleXml($attr);
    }

    public function setStyleXml(SimpleXMLElement $styleXml): void
    {
        $this->styleXml = $styleXml;
    }

    public function setTheme(Theme $theme): void
    {
        $this->theme = $theme;
    }

    public function setStyleBaseData(?Theme $theme = null, array $styles = [], array $cellStyles = []): void
    {
        $this->theme = $theme;
        $this->styles = $styles;
        $this->cellStyles = $cellStyles;
    }

    public function readFontStyle(Font $fontStyle, SimpleXMLElement $fontStyleXml): void
    {
        if (isset($fontStyleXml->name)) {
            $attr = $this->getStyleAttributes($fontStyleXml->name);
            if (isset($attr['val'])) {
                $fontStyle->setName((string) $attr['val']);
            }
        }
        if (isset($fontStyleXml->sz)) {
            $attr = $this->getStyleAttributes($fontStyleXml->sz);
            if (isset($attr['val'])) {
                $fontStyle->setSize((float) $attr['val']);
            }
        }
        if (isset($fontStyleXml->b)) {
            $attr = $this->getStyleAttributes($fontStyleXml->b);
            $fontStyle->setBold(!isset($attr['val']) || self::boolean((string) $attr['val']));
        }
        if (isset($fontStyleXml->i)) {
            $attr = $this->getStyleAttributes($fontStyleXml->i);
            $fontStyle->setItalic(!isset($attr['val']) || self::boolean((string) $attr['val']));
        }
        if (isset($fontStyleXml->strike)) {
            $attr = $this->getStyleAttributes($fontStyleXml->strike);
            $fontStyle->setStrikethrough(!isset($attr['val']) || self::boolean((string) $attr['val']));
        }
        $fontStyle->getColor()->setARGB($this->readColor($fontStyleXml->color));

        if (isset($fontStyleXml->u)) {
            $attr = $this->getStyleAttributes($fontStyleXml->u);
            if (!isset($attr['val'])) {
                $fontStyle->setUnderline(Font::UNDERLINE_SINGLE);
            } else {
                $fontStyle->setUnderline((string) $attr['val']);
            }
        }
        if (isset($fontStyleXml->vertAlign)) {
            $attr = $this->getStyleAttributes($fontStyleXml->vertAlign);
            if (isset($attr['val'])) {
                $verticalAlign = strtolower((string) $attr['val']);
                if ($verticalAlign === 'superscript') {
                    $fontStyle->setSuperscript(true);
                } elseif ($verticalAlign === 'subscript') {
                    $fontStyle->setSubscript(true);
                }
            }
        }
        if (isset($fontStyleXml->scheme)) {
            $attr = $this->getStyleAttributes($fontStyleXml->scheme);
            $fontStyle->setScheme((string) $attr['val']);
        }
    }

    private function readNumberFormat(NumberFormat $numfmtStyle, SimpleXMLElement $numfmtStyleXml): void
    {
        if ((string) $numfmtStyleXml['formatCode'] !== '') {
            $numfmtStyle->setFormatCode(self::formatGeneral((string) $numfmtStyleXml['formatCode']));

            return;
        }
        $numfmt = $this->getStyleAttributes($numfmtStyleXml);
        if (isset($numfmt['formatCode'])) {
            $numfmtStyle->setFormatCode(self::formatGeneral((string) $numfmt['formatCode']));
        }
    }

    public function readFillStyle(Fill $fillStyle, SimpleXMLElement $fillStyleXml): void
    {
        if ($fillStyleXml->gradientFill) {
            
            $gradientFill = $fillStyleXml->gradientFill[0];
            $attr = $this->getStyleAttributes($gradientFill);
            if (!empty($attr['type'])) {
                $fillStyle->setFillType((string) $attr['type']);
            }
            $fillStyle->setRotation((float) ($attr['degree']));
            $gradientFill->registerXPathNamespace('sml', Namespaces::MAIN);
            $fillStyle->getStartColor()->setARGB($this->readColor(self::getArrayItem($gradientFill->xpath('sml:stop[@position=0]'))->color)); /
    public function readStyle(Style $docStyle, SimpleXMLElement|stdClass $style): void
    {
        if ($style instanceof SimpleXMLElement) {
            $this->readNumberFormat($docStyle->getNumberFormat(), $style->numFmt);
        } else {
            $docStyle->getNumberFormat()->setFormatCode(self::formatGeneral((string) $style->numFmt));
        }

        if (isset($style->font)) {
            $this->readFontStyle($docStyle->getFont(), $style->font);
        }

        if (isset($style->fill)) {
            $this->readFillStyle($docStyle->getFill(), $style->fill);
        }

        if (isset($style->border)) {
            $this->readBorderStyle($docStyle->getBorders(), $style->border);
        }

        if (isset($style->alignment)) {
            $this->readAlignmentStyle($docStyle->getAlignment(), $style->alignment);
        }

        
        if (isset($style->protection)) {
            $this->readProtectionLocked($docStyle, $style->protection);
            $this->readProtectionHidden($docStyle, $style->protection);
        }

        
        if (isset($style->quotePrefix)) {
            $docStyle->setQuotePrefix((bool) $style->quotePrefix);
        }
    }

    
    public function readProtectionLocked(Style $docStyle, SimpleXMLElement $style): void
    {
        $locked = '';
        if ((string) $style['locked'] !== '') {
            $locked = (string) $style['locked'];
        } else {
            $attr = $this->getStyleAttributes($style);
            if (isset($attr['locked'])) {
                $locked = (string) $attr['locked'];
            }
        }
        if ($locked !== '') {
            if (self::boolean($locked)) {
                $docStyle->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            } else {
                $docStyle->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
            }
        }
    }

    
    public function readProtectionHidden(Style $docStyle, SimpleXMLElement $style): void
    {
        $hidden = '';
        if ((string) $style['hidden'] !== '') {
            $hidden = (string) $style['hidden'];
        } else {
            $attr = $this->getStyleAttributes($style);
            if (isset($attr['hidden'])) {
                $hidden = (string) $attr['hidden'];
            }
        }
        if ($hidden !== '') {
            if (self::boolean((string) $hidden)) {
                $docStyle->getProtection()->setHidden(Protection::PROTECTION_PROTECTED);
            } else {
                $docStyle->getProtection()->setHidden(Protection::PROTECTION_UNPROTECTED);
            }
        }
    }

    public function readColor(SimpleXMLElement $color, bool $background = false): string
    {
        $attr = $this->getStyleAttributes($color);
        if (isset($attr['rgb'])) {
            return (string) $attr['rgb'];
        }
        if (isset($attr['indexed'])) {
            $indexedColor = (int) $attr['indexed'];
            if ($indexedColor >= count($this->workbookPalette)) {
                return Color::indexedColor($indexedColor - 7, $background)->getARGB() ?? '';
            }

            return Color::indexedColor($indexedColor, $background, $this->workbookPalette)->getARGB() ?? '';
        }
        if (isset($attr['theme'])) {
            if ($this->theme !== null) {
                $returnColour = $this->theme->getColourByIndex((int) $attr['theme']);
                if (isset($attr['tint'])) {
                    $tintAdjust = (float) $attr['tint'];
                    $returnColour = Color::changeBrightness($returnColour ?? '', $tintAdjust);
                }

                return 'FF' . $returnColour;
            }
        }

        return ($background) ? 'FFFFFFFF' : 'FF000000';
    }

    public function dxfs(bool $readDataOnly = false): array
    {
        $dxfs = [];
        if (!$readDataOnly && $this->styleXml) {
            
            if ($this->styleXml->dxfs) {
                foreach ($this->styleXml->dxfs->dxf as $dxf) {
                    $style = new Style(false, true);
                    $this->readStyle($style, $dxf);
                    $dxfs[] = $style;
                }
            }
            
            if ($this->styleXml->cellStyles) {
                foreach ($this->styleXml->cellStyles->cellStyle as $cellStylex) {
                    $cellStyle = Xlsx::getAttributes($cellStylex);
                    if ((int) ($cellStyle['builtinId']) == 0) {
                        if (isset($this->cellStyles[(int) ($cellStyle['xfId'])])) {
                            
                            $style = new Style();
                            $this->readStyle($style, $this->cellStyles[(int) ($cellStyle['xfId'])]);

                            
                        }
                    }
                }
            }
        }

        return $dxfs;
    }

    
    public function tableStyles(bool $readDataOnly = false): array
    {
        $tableStyles = [];
        if (!$readDataOnly && $this->styleXml) {
            
            if ($this->styleXml->tableStyles) {
                foreach ($this->styleXml->tableStyles->tableStyle as $s) {
                    $attrs = Xlsx::getAttributes($s);
                    if (isset($attrs['name'][0])) {
                        $style = new TableDxfsStyle((string) ($attrs['name'][0]));
                        foreach ($s->tableStyleElement as $e) {
                            $a = Xlsx::getAttributes($e);
                            if (isset($a['dxfId'][0], $a['type'][0])) {
                                switch ($a['type'][0]) {
                                    case 'headerRow':
                                        $style->setHeaderRow((int) ($a['dxfId'][0]));

                                        break;
                                    case 'firstRowStripe':
                                        $style->setFirstRowStripe((int) ($a['dxfId'][0]));

                                        break;
                                    case 'secondRowStripe':
                                        $style->setSecondRowStripe((int) ($a['dxfId'][0]));

                                        break;
                                    default:
                                }
                            }
                        }
                        $tableStyles[] = $style;
                    }
                }
            }
        }

        return $tableStyles;
    }

    public function styles(): array
    {
        return $this->styles;
    }

    
    private static function getArrayItem(mixed $array): ?SimpleXMLElement
    {
        return is_array($array) ? ($array[0] ?? null) : null; 
    }
}
