<?php

namespace PhpOffice\PhpSpreadsheet\RichText;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Style\Font;

class Run extends TextElement implements ITextElement
{
    
    private ?Font $font;

    
    public function __construct(string $text = '')
    {
        parent::__construct($text);
        
        $this->font = new Font();
    }

    
    public function getFont(): ?Font
    {
        return $this->font;
    }

    public function getFontOrThrow(): Font
    {
        if ($this->font === null) {
            throw new SpreadsheetException('unexpected null font');
        }

        return $this->font;
    }

    
    public function setFont(?Font $font = null): static
    {
        $this->font = $font;

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->getText()
            . (($this->font === null) ? '' : $this->font->getHashCode())
            . __CLASS__
        );
    }
}
