<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use PhpOffice\PhpSpreadsheet\IComparable;
use PhpOffice\PhpSpreadsheet\Style\Color;

class Shadow implements IComparable
{
    
    const SHADOW_BOTTOM = 'b';
    const SHADOW_BOTTOM_LEFT = 'bl';
    const SHADOW_BOTTOM_RIGHT = 'br';
    const SHADOW_CENTER = 'ctr';
    const SHADOW_LEFT = 'l';
    const SHADOW_TOP = 't';
    const SHADOW_TOP_LEFT = 'tl';
    const SHADOW_TOP_RIGHT = 'tr';

    
    private bool $visible;

    
    private int $blurRadius;

    
    private int $distance;

    
    private int $direction;

    
    private string $alignment;

    
    private Color $color;

    
    private int $alpha;

    
    public function __construct()
    {
        
        $this->visible = false;
        $this->blurRadius = 6;
        $this->distance = 2;
        $this->direction = 0;
        $this->alignment = self::SHADOW_BOTTOM_RIGHT;
        $this->color = new Color(Color::COLOR_BLACK);
        $this->alpha = 50;
    }

    
    public function getVisible(): bool
    {
        return $this->visible;
    }

    
    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    
    public function getBlurRadius(): int
    {
        return $this->blurRadius;
    }

    
    public function setBlurRadius(int $blurRadius): static
    {
        $this->blurRadius = $blurRadius;

        return $this;
    }

    
    public function getDistance(): int
    {
        return $this->distance;
    }

    
    public function setDistance(int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    
    public function getDirection(): int
    {
        return $this->direction;
    }

    
    public function setDirection(int $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    
    public function getAlignment(): string
    {
        return $this->alignment;
    }

    
    public function setAlignment(string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    
    public function getColor(): Color
    {
        return $this->color;
    }

    
    public function setColor(Color $color): static
    {
        $this->color = $color;

        return $this;
    }

    
    public function getAlpha(): int
    {
        return $this->alpha;
    }

    
    public function setAlpha(int $alpha): static
    {
        $this->alpha = $alpha;

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            ($this->visible ? 't' : 'f')
            . $this->blurRadius
            . $this->distance
            . $this->direction
            . $this->alignment
            . $this->color->getHashCode()
            . $this->alpha
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
}
