<?php

namespace PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Helper\Size;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Stringable;

class Comment implements IComparable, Stringable
{
    
    private string $author;

    
    private RichText $text;

    
    private string $width = '96pt';

    
    private string $marginLeft = '59.25pt';

    
    private string $marginTop = '1.5pt';

    
    private bool $visible = false;

    
    private string $height = '55.5pt';

    
    private Color $fillColor;

    
    private string $alignment;

    
    private Drawing $backgroundImage;

    public const TEXTBOX_DIRECTION_RTL = 'rtl';
    public const TEXTBOX_DIRECTION_LTR = 'ltr';
    
    public const TEXTBOX_DIRECTION_AUTO = 'auto';
    public const TEXTBOX_DIRECTION_CONTEXT = 'auto';

    private string $textboxDirection = '';

    
    public function __construct()
    {
        
        $this->author = 'Author';
        $this->text = new RichText();
        $this->fillColor = new Color('FFFFFFE1');
        $this->alignment = Alignment::HORIZONTAL_GENERAL;
        $this->backgroundImage = new Drawing();
    }

    
    public function getAuthor(): string
    {
        return $this->author;
    }

    
    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    
    public function getText(): RichText
    {
        return $this->text;
    }

    
    public function setText(RichText $text): self
    {
        $this->text = $text;

        return $this;
    }

    
    public function getWidth(): string
    {
        return $this->width;
    }

    
    public function setWidth(string $width): self
    {
        $width = new Size($width);
        if ($width->valid()) {
            $this->width = (string) $width;
        }

        return $this;
    }

    
    public function getHeight(): string
    {
        return $this->height;
    }

    
    public function setHeight(string $height): self
    {
        $height = new Size($height);
        if ($height->valid()) {
            $this->height = (string) $height;
        }

        return $this;
    }

    
    public function getMarginLeft(): string
    {
        return $this->marginLeft;
    }

    
    public function setMarginLeft(string $margin): self
    {
        $margin = new Size($margin);
        if ($margin->valid()) {
            $this->marginLeft = (string) $margin;
        }

        return $this;
    }

    
    public function getMarginTop(): string
    {
        return $this->marginTop;
    }

    
    public function setMarginTop(string $margin): self
    {
        $margin = new Size($margin);
        if ($margin->valid()) {
            $this->marginTop = (string) $margin;
        }

        return $this;
    }

    
    public function getVisible(): bool
    {
        return $this->visible;
    }

    
    public function setVisible(bool $visibility): self
    {
        $this->visible = $visibility;

        return $this;
    }

    
    public function setFillColor(Color $color): self
    {
        $this->fillColor = $color;

        return $this;
    }

    
    public function getFillColor(): Color
    {
        return $this->fillColor;
    }

    public function setAlignment(string $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getAlignment(): string
    {
        return $this->alignment;
    }

    public function setTextboxDirection(string $textboxDirection): self
    {
        $this->textboxDirection = $textboxDirection;

        return $this;
    }

    public function getTextboxDirection(): string
    {
        return $this->textboxDirection;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->author
            . $this->text->getHashCode()
            . $this->width
            . $this->height
            . $this->marginLeft
            . $this->marginTop
            . ($this->visible ? 1 : 0)
            . $this->fillColor->getHashCode()
            . $this->alignment
            . $this->textboxDirection
            . ($this->hasBackgroundImage() ? $this->backgroundImage->getHashCode() : '')
            . __CLASS__
        );
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $this->$key = clone $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    
    public function __toString(): string
    {
        return $this->text->getPlainText();
    }

    
    public function hasBackgroundImage(): bool
    {
        $path = $this->backgroundImage->getPath();

        if (empty($path)) {
            return false;
        }

        return getimagesize($path) !== false;
    }

    
    public function getBackgroundImage(): Drawing
    {
        return $this->backgroundImage;
    }

    
    public function setBackgroundImage(Drawing $objDrawing): self
    {
        if (!array_key_exists($objDrawing->getType(), Drawing::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }
        $this->backgroundImage = $objDrawing;

        return $this;
    }

    
    public function setSizeAsBackgroundImage(): self
    {
        if ($this->hasBackgroundImage()) {
            $this->setWidth(SharedDrawing::pixelsToPoints($this->backgroundImage->getWidth()) . 'pt');
            $this->setHeight(SharedDrawing::pixelsToPoints($this->backgroundImage->getHeight()) . 'pt');
        }

        return $this;
    }
}
