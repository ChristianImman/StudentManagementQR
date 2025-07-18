<?php

namespace PhpOffice\PhpSpreadsheet\RichText;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IComparable;
use Stringable;

class RichText implements IComparable, Stringable
{
    
    private array $richTextElements;

    
    public function __construct(?Cell $cell = null)
    {
        
        $this->richTextElements = [];

        
        if ($cell !== null) {
            
            if ($cell->getValueString() !== '') {
                $objRun = new Run($cell->getValueString());
                $objRun->setFont(clone $cell->getWorksheet()->getStyle($cell->getCoordinate())->getFont());
                $this->addText($objRun);
            }

            
            $cell->setValueExplicit($this, DataType::TYPE_STRING);
        }
    }

    
    public function addText(ITextElement $text): static
    {
        $this->richTextElements[] = $text;

        return $this;
    }

    
    public function createText(string $text): TextElement
    {
        $objText = new TextElement($text);
        $this->addText($objText);

        return $objText;
    }

    
    public function createTextRun(string $text): Run
    {
        $objText = new Run($text);
        $this->addText($objText);

        return $objText;
    }

    
    public function getPlainText(): string
    {
        
        $returnValue = '';

        
        foreach ($this->richTextElements as $text) {
            $returnValue .= $text->getText();
        }

        return $returnValue;
    }

    
    public function __toString(): string
    {
        return $this->getPlainText();
    }

    
    public function getRichTextElements(): array
    {
        return $this->richTextElements;
    }

    
    public function setRichTextElements(array $textElements): static
    {
        $this->richTextElements = $textElements;

        return $this;
    }

    
    public function getHashCode(): string
    {
        $hashElements = '';
        foreach ($this->richTextElements as $element) {
            $hashElements .= $element->getHashCode();
        }

        return md5(
            $hashElements
            . __CLASS__
        );
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            $newValue = is_object($value) ? (clone $value) : $value;
            if (is_array($value)) {
                $newValue = [];
                foreach ($value as $key2 => $value2) {
                    $newValue[$key2] = is_object($value2) ? (clone $value2) : $value2;
                }
            }
            $this->$key = $newValue;
        }
    }
}
