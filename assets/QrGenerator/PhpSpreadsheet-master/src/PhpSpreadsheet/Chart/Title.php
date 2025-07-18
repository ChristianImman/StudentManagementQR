<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;

class Title
{
    public const TITLE_CELL_REFERENCE
        = '/^(.*)!' 
        . '[$]([A-Z]{1,3})' 
        . '[$](\d{1,7})$/i'; 

    
    private array|RichText|string $caption;

    
    private bool $overlay = true;

    
    private ?Layout $layout;

    private string $cellReference = '';

    private ?Font $font = null;

    
    public function __construct(array|RichText|string $caption = '', ?Layout $layout = null, bool $overlay = false)
    {
        $this->caption = $caption;
        $this->layout = $layout;
        $this->setOverlay($overlay);
    }

    
    public function getCaption(): array|RichText|string
    {
        return $this->caption;
    }

    public function getCaptionText(?Spreadsheet $spreadsheet = null): string
    {
        if ($spreadsheet !== null) {
            $caption = $this->getCalculatedTitle($spreadsheet);
            if ($caption !== null) {
                return $caption;
            }
        }
        $caption = $this->caption;
        if (is_string($caption)) {
            return $caption;
        }
        if ($caption instanceof RichText) {
            return $caption->getPlainText();
        }
        $retVal = '';
        foreach ($caption as $textx) {
            
            $text = $textx;
            if ($text instanceof RichText) {
                $retVal .= $text->getPlainText();
            } else {
                $retVal .= $text;
            }
        }

        return $retVal;
    }

    
    public function setCaption(array|RichText|string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    
    public function getOverlay(): bool
    {
        return $this->overlay;
    }

    
    public function setOverlay(bool $overlay): self
    {
        $this->overlay = $overlay;

        return $this;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function setCellReference(string $cellReference): self
    {
        $this->cellReference = $cellReference;

        return $this;
    }

    public function getCellReference(): string
    {
        return $this->cellReference;
    }

    public function getCalculatedTitle(?Spreadsheet $spreadsheet): ?string
    {
        preg_match(self::TITLE_CELL_REFERENCE, $this->cellReference, $matches);
        if (count($matches) === 0 || $spreadsheet === null) {
            return null;
        }
        $sheetName = preg_replace("/^'(.*)'$/", '$1', $matches[1]) ?? '';

        return $spreadsheet->getSheetByName($sheetName)?->getCell($matches[2] . $matches[3])?->getFormattedValue();
    }

    public function getFont(): ?Font
    {
        return $this->font;
    }

    public function setFont(?Font $font): self
    {
        $this->font = $font;

        return $this;
    }

    
    public function __clone()
    {
        $this->layout = ($this->layout === null) ? null : clone $this->layout;
        $this->font = ($this->font === null) ? null : clone $this->font;
        if (is_array($this->caption)) {
            $captions = $this->caption;
            $this->caption = [];
            foreach ($captions as $caption) {
                $this->caption[] = is_object($caption) ? (clone $caption) : $caption;
            }
        } else {
            $this->caption = is_object($this->caption) ? (clone $this->caption) : $this->caption;
        }
    }
}
