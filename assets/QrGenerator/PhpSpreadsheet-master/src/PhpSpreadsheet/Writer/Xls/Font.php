<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Font
{
    
    private int $colorIndex;

    
    private \PhpOffice\PhpSpreadsheet\Style\Font $font;

    
    public function __construct(\PhpOffice\PhpSpreadsheet\Style\Font $font)
    {
        $this->colorIndex = 0x7FFF;
        $this->font = $font;
    }

    
    public function setColorIndex(int $colorIndex): void
    {
        $this->colorIndex = $colorIndex;
    }

    private static int $notImplemented = 0;

    
    public function writeFont(): string
    {
        $font_outline = self::$notImplemented;
        $font_shadow = self::$notImplemented;

        $icv = $this->colorIndex; 
        if ($this->font->getSuperscript()) {
            $sss = 1;
        } elseif ($this->font->getSubscript()) {
            $sss = 2;
        } else {
            $sss = 0;
        }
        $bFamily = 0; 
        $bCharSet = \PhpOffice\PhpSpreadsheet\Shared\Font::getCharsetFromFontName((string) $this->font->getName()); 

        $record = 0x31; 
        $reserved = 0x00; 
        $grbit = 0x00; 
        if ($this->font->getItalic()) {
            $grbit |= 0x02;
        }
        if ($this->font->getStrikethrough()) {
            $grbit |= 0x08;
        }
        if ($font_outline) {
            $grbit |= 0x10;
        }
        if ($font_shadow) {
            $grbit |= 0x20;
        }

        $data = pack(
            'vvvvvCCCC',
            
            $this->font->getSize() * 20,
            $grbit,
            
            $icv,
            
            self::mapBold($this->font->getBold()),
            
            $sss,
            self::mapUnderline((string) $this->font->getUnderline()),
            $bFamily,
            $bCharSet,
            $reserved
        );
        $data .= StringHelper::UTF8toBIFF8UnicodeShort((string) $this->font->getName());

        $length = strlen($data);
        $header = pack('vv', $record, $length);

        return $header . $data;
    }

    
    private static function mapBold(?bool $bold): int
    {
        if ($bold === true) {
            return 0x2BC; 
        }

        return 0x190; 
    }

    
    private static array $mapUnderline = [
        \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE => 0x00,
        \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE => 0x01,
        \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_DOUBLE => 0x02,
        \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLEACCOUNTING => 0x21,
        \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_DOUBLEACCOUNTING => 0x22,
    ];

    
    private static function mapUnderline(string $underline): int
    {
        if (isset(self::$mapUnderline[$underline])) {
            return self::$mapUnderline[$underline];
        }

        return 0x00;
    }
}
