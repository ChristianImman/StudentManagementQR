<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;


class HeaderFooter
{
    
    const IMAGE_HEADER_LEFT = 'LH';
    const IMAGE_HEADER_CENTER = 'CH';
    const IMAGE_HEADER_RIGHT = 'RH';
    const IMAGE_FOOTER_LEFT = 'LF';
    const IMAGE_FOOTER_CENTER = 'CF';
    const IMAGE_FOOTER_RIGHT = 'RF';

    
    private string $oddHeader = '';

    
    private string $oddFooter = '';

    
    private string $evenHeader = '';

    
    private string $evenFooter = '';

    
    private string $firstHeader = '';

    
    private string $firstFooter = '';

    
    private bool $differentOddEven = false;

    
    private bool $differentFirst = false;

    
    private bool $scaleWithDocument = true;

    
    private bool $alignWithMargins = true;

    
    private array $headerFooterImages = [];

    
    public function __construct()
    {
    }

    
    public function getOddHeader(): string
    {
        return $this->oddHeader;
    }

    
    public function setOddHeader(string $oddHeader): static
    {
        $this->oddHeader = $oddHeader;

        return $this;
    }

    
    public function getOddFooter(): string
    {
        return $this->oddFooter;
    }

    
    public function setOddFooter(string $oddFooter): static
    {
        $this->oddFooter = $oddFooter;

        return $this;
    }

    
    public function getEvenHeader(): string
    {
        return $this->evenHeader;
    }

    
    public function setEvenHeader(string $eventHeader): static
    {
        $this->evenHeader = $eventHeader;

        return $this;
    }

    
    public function getEvenFooter(): string
    {
        return $this->evenFooter;
    }

    
    public function setEvenFooter(string $evenFooter): static
    {
        $this->evenFooter = $evenFooter;

        return $this;
    }

    
    public function getFirstHeader(): string
    {
        return $this->firstHeader;
    }

    
    public function setFirstHeader(string $firstHeader): static
    {
        $this->firstHeader = $firstHeader;

        return $this;
    }

    
    public function getFirstFooter(): string
    {
        return $this->firstFooter;
    }

    
    public function setFirstFooter(string $firstFooter): static
    {
        $this->firstFooter = $firstFooter;

        return $this;
    }

    
    public function getDifferentOddEven(): bool
    {
        return $this->differentOddEven;
    }

    
    public function setDifferentOddEven(bool $differentOddEvent): static
    {
        $this->differentOddEven = $differentOddEvent;

        return $this;
    }

    
    public function getDifferentFirst(): bool
    {
        return $this->differentFirst;
    }

    
    public function setDifferentFirst(bool $differentFirst): static
    {
        $this->differentFirst = $differentFirst;

        return $this;
    }

    
    public function getScaleWithDocument(): bool
    {
        return $this->scaleWithDocument;
    }

    
    public function setScaleWithDocument(bool $scaleWithDocument): static
    {
        $this->scaleWithDocument = $scaleWithDocument;

        return $this;
    }

    
    public function getAlignWithMargins(): bool
    {
        return $this->alignWithMargins;
    }

    
    public function setAlignWithMargins(bool $alignWithMargins): static
    {
        $this->alignWithMargins = $alignWithMargins;

        return $this;
    }

    
    public function addImage(HeaderFooterDrawing $image, string $location = self::IMAGE_HEADER_LEFT): static
    {
        $this->headerFooterImages[$location] = $image;

        return $this;
    }

    
    public function removeImage(string $location = self::IMAGE_HEADER_LEFT): static
    {
        if (isset($this->headerFooterImages[$location])) {
            unset($this->headerFooterImages[$location]);
        }

        return $this;
    }

    
    public function setImages(array $images): static
    {
        $this->headerFooterImages = $images;

        return $this;
    }

    
    public function getImages(): array
    {
        
        $images = [];
        if (isset($this->headerFooterImages[self::IMAGE_HEADER_LEFT])) {
            $images[self::IMAGE_HEADER_LEFT] = $this->headerFooterImages[self::IMAGE_HEADER_LEFT];
        }
        if (isset($this->headerFooterImages[self::IMAGE_HEADER_CENTER])) {
            $images[self::IMAGE_HEADER_CENTER] = $this->headerFooterImages[self::IMAGE_HEADER_CENTER];
        }
        if (isset($this->headerFooterImages[self::IMAGE_HEADER_RIGHT])) {
            $images[self::IMAGE_HEADER_RIGHT] = $this->headerFooterImages[self::IMAGE_HEADER_RIGHT];
        }
        if (isset($this->headerFooterImages[self::IMAGE_FOOTER_LEFT])) {
            $images[self::IMAGE_FOOTER_LEFT] = $this->headerFooterImages[self::IMAGE_FOOTER_LEFT];
        }
        if (isset($this->headerFooterImages[self::IMAGE_FOOTER_CENTER])) {
            $images[self::IMAGE_FOOTER_CENTER] = $this->headerFooterImages[self::IMAGE_FOOTER_CENTER];
        }
        if (isset($this->headerFooterImages[self::IMAGE_FOOTER_RIGHT])) {
            $images[self::IMAGE_FOOTER_RIGHT] = $this->headerFooterImages[self::IMAGE_FOOTER_RIGHT];
        }
        $this->headerFooterImages = $images;

        return $this->headerFooterImages;
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
}
