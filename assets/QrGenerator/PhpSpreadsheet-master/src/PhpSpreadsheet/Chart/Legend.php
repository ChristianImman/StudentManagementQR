<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

class Legend
{
    
    const XL_LEGEND_POSITION_BOTTOM = -4107; 
    const XL_LEGEND_POSITION_CORNER = 2; 
    const XL_LEGEND_POSITION_CUSTOM = -4161; 
    const XL_LEGEND_POSITION_LEFT = -4131; 
    const XL_LEGEND_POSITION_RIGHT = -4152; 
    const XL_LEGEND_POSITION_TOP = -4160; 

    const POSITION_RIGHT = 'r';
    const POSITION_LEFT = 'l';
    const POSITION_BOTTOM = 'b';
    const POSITION_TOP = 't';
    const POSITION_TOPRIGHT = 'tr';

    const POSITION_XLREF = [
        self::XL_LEGEND_POSITION_BOTTOM => self::POSITION_BOTTOM,
        self::XL_LEGEND_POSITION_CORNER => self::POSITION_TOPRIGHT,
        self::XL_LEGEND_POSITION_CUSTOM => '??',
        self::XL_LEGEND_POSITION_LEFT => self::POSITION_LEFT,
        self::XL_LEGEND_POSITION_RIGHT => self::POSITION_RIGHT,
        self::XL_LEGEND_POSITION_TOP => self::POSITION_TOP,
    ];

    
    private string $position = self::POSITION_RIGHT;

    
    private bool $overlay = true;

    
    private ?Layout $layout;

    private GridLines $borderLines;

    private ChartColor $fillColor;

    private ?AxisText $legendText = null;

    
    public function __construct(string $position = self::POSITION_RIGHT, ?Layout $layout = null, bool $overlay = false)
    {
        $this->setPosition($position);
        $this->layout = $layout;
        $this->setOverlay($overlay);
        $this->borderLines = new GridLines();
        $this->fillColor = new ChartColor();
    }

    public function getFillColor(): ChartColor
    {
        return $this->fillColor;
    }

    
    public function getPosition(): string
    {
        return $this->position;
    }

    
    public function setPosition(string $position): bool
    {
        if (!in_array($position, self::POSITION_XLREF)) {
            return false;
        }

        $this->position = $position;

        return true;
    }

    
    public function getPositionXL(): false|int
    {
        return array_search($this->position, self::POSITION_XLREF);
    }

    
    public function setPositionXL(int $positionXL): bool
    {
        if (!isset(self::POSITION_XLREF[$positionXL])) {
            return false;
        }

        $this->position = self::POSITION_XLREF[$positionXL];

        return true;
    }

    
    public function getOverlay(): bool
    {
        return $this->overlay;
    }

    
    public function setOverlay(bool $overlay): void
    {
        $this->overlay = $overlay;
    }

    
    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function getLegendText(): ?AxisText
    {
        return $this->legendText;
    }

    public function setLegendText(?AxisText $legendText): self
    {
        $this->legendText = $legendText;

        return $this;
    }

    public function getBorderLines(): GridLines
    {
        return $this->borderLines;
    }

    public function setBorderLines(GridLines $borderLines): self
    {
        $this->borderLines = $borderLines;

        return $this;
    }

    
    public function __clone()
    {
        $this->layout = ($this->layout === null) ? null : clone $this->layout;
        $this->legendText = ($this->legendText === null) ? null : clone $this->legendText;
        $this->borderLines = clone $this->borderLines;
        $this->fillColor = clone $this->fillColor;
    }
}
