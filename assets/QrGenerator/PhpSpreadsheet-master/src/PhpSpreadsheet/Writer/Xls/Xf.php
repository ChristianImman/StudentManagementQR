<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Style\CellAlignment;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Style\CellBorder;
use PhpOffice\PhpSpreadsheet\Writer\Xls\Style\CellFill;




class Xf
{
    
    private bool $isStyleXf;

    
    private int $fontIndex;

    
    private int $numberFormatIndex;

    
    private int $textJustLast;

    
    private int $foregroundColor;

    
    private int $backgroundColor;

    
    private int $bottomBorderColor;

    
    private int $topBorderColor;

    
    private int $leftBorderColor;

    
    private int $rightBorderColor;

    
    private int $diagColor;

    private Style $style;

    
    public function __construct(Style $style)
    {
        $this->isStyleXf = false;
        $this->fontIndex = 0;

        $this->numberFormatIndex = 0;

        $this->textJustLast = 0;

        $this->foregroundColor = 0x40;
        $this->backgroundColor = 0x41;

        

        $this->bottomBorderColor = 0x40;
        $this->topBorderColor = 0x40;
        $this->leftBorderColor = 0x40;
        $this->rightBorderColor = 0x40;
        $this->diagColor = 0x40;
        $this->style = $style;
    }

    
    public function writeXf(): string
    {
        
        if ($this->isStyleXf) {
            $style = 0xFFF5;
        } else {
            $style = self::mapLocked($this->style->getProtection()->getLocked());
            $style |= self::mapHidden($this->style->getProtection()->getHidden()) << 1;
        }

        
        $atr_num = ($this->numberFormatIndex != 0) ? 1 : 0;
        $atr_fnt = ($this->fontIndex != 0) ? 1 : 0;
        $atr_alc = ((int) $this->style->getAlignment()->getWrapText()) ? 1 : 0;
        $atr_bdr = (CellBorder::style($this->style->getBorders()->getBottom())
            || CellBorder::style($this->style->getBorders()->getTop())
            || CellBorder::style($this->style->getBorders()->getLeft())
            || CellBorder::style($this->style->getBorders()->getRight())) ? 1 : 0;
        $atr_pat = ($this->foregroundColor != 0x40) ? 1 : 0;
        $atr_pat = ($this->backgroundColor != 0x41) ? 1 : $atr_pat;
        $atr_pat = CellFill::style($this->style->getFill()) ? 1 : $atr_pat;
        $atr_prot = self::mapLocked($this->style->getProtection()->getLocked())
            | self::mapHidden($this->style->getProtection()->getHidden());

        
        if (CellBorder::style($this->style->getBorders()->getBottom()) == 0) {
            $this->bottomBorderColor = 0;
        }
        if (CellBorder::style($this->style->getBorders()->getTop()) == 0) {
            $this->topBorderColor = 0;
        }
        if (CellBorder::style($this->style->getBorders()->getRight()) == 0) {
            $this->rightBorderColor = 0;
        }
        if (CellBorder::style($this->style->getBorders()->getLeft()) == 0) {
            $this->leftBorderColor = 0;
        }
        if (CellBorder::style($this->style->getBorders()->getDiagonal()) == 0) {
            $this->diagColor = 0;
        }

        $record = 0x00E0; 
        $length = 0x0014; 

        $ifnt = $this->fontIndex; 
        $ifmt = $this->numberFormatIndex; 

        
        $align = CellAlignment::horizontal($this->style->getAlignment());
        $align |= CellAlignment::wrap($this->style->getAlignment()) << 3;
        $align |= CellAlignment::vertical($this->style->getAlignment()) << 4;
        $align |= $this->textJustLast << 7;

        $used_attrib = $atr_num << 2;
        $used_attrib |= $atr_fnt << 3;
        $used_attrib |= $atr_alc << 4;
        $used_attrib |= $atr_bdr << 5;
        $used_attrib |= $atr_pat << 6;
        $used_attrib |= $atr_prot << 7;

        $icv = $this->foregroundColor; 
        $icv |= $this->backgroundColor << 7;

        $border1 = CellBorder::style($this->style->getBorders()->getLeft()); 
        $border1 |= CellBorder::style($this->style->getBorders()->getRight()) << 4;
        $border1 |= CellBorder::style($this->style->getBorders()->getTop()) << 8;
        $border1 |= CellBorder::style($this->style->getBorders()->getBottom()) << 12;
        $border1 |= $this->leftBorderColor << 16;
        $border1 |= $this->rightBorderColor << 23;

        $diagonalDirection = $this->style->getBorders()->getDiagonalDirection();
        $diag_tl_to_rb = $diagonalDirection == Borders::DIAGONAL_BOTH
            || $diagonalDirection == Borders::DIAGONAL_DOWN;
        $diag_tr_to_lb = $diagonalDirection == Borders::DIAGONAL_BOTH
            || $diagonalDirection == Borders::DIAGONAL_UP;
        $border1 |= $diag_tl_to_rb << 30;
        $border1 |= $diag_tr_to_lb << 31;

        $border2 = $this->topBorderColor; 
        $border2 |= $this->bottomBorderColor << 7;
        $border2 |= $this->diagColor << 14;
        $border2 |= CellBorder::style($this->style->getBorders()->getDiagonal()) << 21;
        $border2 |= CellFill::style($this->style->getFill()) << 26;

        $header = pack('vv', $record, $length);

        
        $biff8_options = $this->style->getAlignment()->getIndent();
        $biff8_options |= (int) $this->style->getAlignment()->getShrinkToFit() << 4;

        $data = pack('vvvC', $ifnt, $ifmt, $style, $align);
        $data .= pack('CCC', self::mapTextRotation((int) $this->style->getAlignment()->getTextRotation()), $biff8_options, $used_attrib);
        $data .= pack('VVv', $border1, $border2, $icv);

        return $header . $data;
    }

    
    public function setIsStyleXf(bool $value): void
    {
        $this->isStyleXf = $value;
    }

    
    public function setBottomColor(int $colorIndex): void
    {
        $this->bottomBorderColor = $colorIndex;
    }

    
    public function setTopColor(int $colorIndex): void
    {
        $this->topBorderColor = $colorIndex;
    }

    
    public function setLeftColor(int $colorIndex): void
    {
        $this->leftBorderColor = $colorIndex;
    }

    
    public function setRightColor(int $colorIndex): void
    {
        $this->rightBorderColor = $colorIndex;
    }

    
    public function setDiagColor(int $colorIndex): void
    {
        $this->diagColor = $colorIndex;
    }

    
    public function setFgColor(int $colorIndex): void
    {
        $this->foregroundColor = $colorIndex;
    }

    
    public function setBgColor(int $colorIndex): void
    {
        $this->backgroundColor = $colorIndex;
    }

    
    public function setNumberFormatIndex(int $numberFormatIndex): void
    {
        $this->numberFormatIndex = $numberFormatIndex;
    }

    
    public function setFontIndex(int $value): void
    {
        $this->fontIndex = $value;
    }

    
    private static function mapTextRotation(int $textRotation): int
    {
        if ($textRotation >= 0) {
            return $textRotation;
        }
        if ($textRotation == Alignment::TEXTROTATION_STACK_PHPSPREADSHEET) {
            return Alignment::TEXTROTATION_STACK_EXCEL;
        }

        return 90 - $textRotation;
    }

    private const LOCK_ARRAY = [
        Protection::PROTECTION_INHERIT => 1,
        Protection::PROTECTION_PROTECTED => 1,
        Protection::PROTECTION_UNPROTECTED => 0,
    ];

    
    private static function mapLocked(?string $locked): int
    {
        return $locked !== null && array_key_exists($locked, self::LOCK_ARRAY) ? self::LOCK_ARRAY[$locked] : 1;
    }

    private const HIDDEN_ARRAY = [
        Protection::PROTECTION_INHERIT => 0,
        Protection::PROTECTION_PROTECTED => 1,
        Protection::PROTECTION_UNPROTECTED => 0,
    ];

    
    private static function mapHidden(?string $hidden): int
    {
        return $hidden !== null && array_key_exists($hidden, self::HIDDEN_ARRAY) ? self::HIDDEN_ARRAY[$hidden] : 0;
    }
}
