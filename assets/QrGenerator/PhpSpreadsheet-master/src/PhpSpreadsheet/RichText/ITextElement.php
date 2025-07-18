<?php

namespace PhpOffice\PhpSpreadsheet\RichText;

use PhpOffice\PhpSpreadsheet\Style\Font;

interface ITextElement
{
    
    public function getText(): string;

    
    public function setText(string $text): self;

    
    public function getFont(): ?Font;

    
    public function getHashCode(): string;
}
