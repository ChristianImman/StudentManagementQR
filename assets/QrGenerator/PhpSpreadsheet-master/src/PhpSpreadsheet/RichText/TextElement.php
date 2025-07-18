<?php

namespace PhpOffice\PhpSpreadsheet\RichText;

use PhpOffice\PhpSpreadsheet\Style\Font;

class TextElement implements ITextElement
{
    
    private string $text;

    
    public function __construct(string $text = '')
    {
        
        $this->text = $text;
    }

    
    public function getText(): string
    {
        return $this->text;
    }

    
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    
    public function getFont(): ?Font
    {
        return null;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->text
            . __CLASS__
        );
    }
}
