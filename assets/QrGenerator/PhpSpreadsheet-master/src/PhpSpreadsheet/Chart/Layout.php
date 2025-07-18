<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

use PhpOffice\PhpSpreadsheet\Style\Font;

class Layout
{
    
    private ?string $layoutTarget = null;

    
    private ?string $xMode = null;

    
    private ?string $yMode = null;

    
    private ?float $xPos = null;

    
    private ?float $yPos = null;

    
    private ?float $width = null;

    
    private ?float $height = null;

    
    private string $dLblPos = '';

    private string $numFmtCode = '';

    private bool $numFmtLinked = false;

    
    private ?bool $showLegendKey = null;

    
    private ?bool $showVal = null;

    
    private ?bool $showCatName = null;

    
    private ?bool $showSerName = null;

    
    private ?bool $showPercent = null;

    
    private ?bool $showBubbleSize = null;

    
    private ?bool $showLeaderLines = null;

    private ?ChartColor $labelFillColor = null;

    private ?ChartColor $labelBorderColor = null;

    private ?Font $labelFont = null;

    private ?Properties $labelEffects = null;

    
    public function __construct(array $layout = [])
    {
        if (isset($layout['layoutTarget'])) {
            $this->layoutTarget = $layout['layoutTarget'];
        }
        if (isset($layout['xMode'])) {
            $this->xMode = $layout['xMode'];
        }
        if (isset($layout['yMode'])) {
            $this->yMode = $layout['yMode'];
        }
        if (isset($layout['x'])) {
            $this->xPos = (float) $layout['x'];
        }
        if (isset($layout['y'])) {
            $this->yPos = (float) $layout['y'];
        }
        if (isset($layout['w'])) {
            $this->width = (float) $layout['w'];
        }
        if (isset($layout['h'])) {
            $this->height = (float) $layout['h'];
        }
        if (isset($layout['dLblPos'])) {
            $this->dLblPos = (string) $layout['dLblPos'];
        }
        if (isset($layout['numFmtCode'])) {
            $this->numFmtCode = (string) $layout['numFmtCode'];
        }
        $this->initBoolean($layout, 'showLegendKey');
        $this->initBoolean($layout, 'showVal');
        $this->initBoolean($layout, 'showCatName');
        $this->initBoolean($layout, 'showSerName');
        $this->initBoolean($layout, 'showPercent');
        $this->initBoolean($layout, 'showBubbleSize');
        $this->initBoolean($layout, 'showLeaderLines');
        $this->initBoolean($layout, 'numFmtLinked');
        $this->initColor($layout, 'labelFillColor');
        $this->initColor($layout, 'labelBorderColor');
        $labelFont = $layout['labelFont'] ?? null;
        if ($labelFont instanceof Font) {
            $this->labelFont = $labelFont;
        }
        $labelFontColor = $layout['labelFontColor'] ?? null;
        if ($labelFontColor instanceof ChartColor) {
            $this->setLabelFontColor($labelFontColor);
        }
        $labelEffects = $layout['labelEffects'] ?? null;
        if ($labelEffects instanceof Properties) {
            $this->labelEffects = $labelEffects;
        }
    }

    private function initBoolean(array $layout, string $name): void
    {
        if (isset($layout[$name])) {
            $this->$name = (bool) $layout[$name];
        }
    }

    private function initColor(array $layout, string $name): void
    {
        if (isset($layout[$name]) && $layout[$name] instanceof ChartColor) {
            $this->$name = $layout[$name];
        }
    }

    
    public function getLayoutTarget(): ?string
    {
        return $this->layoutTarget;
    }

    
    public function setLayoutTarget(?string $target): static
    {
        $this->layoutTarget = $target;

        return $this;
    }

    
    public function getXMode(): ?string
    {
        return $this->xMode;
    }

    
    public function setXMode(?string $mode): static
    {
        $this->xMode = (string) $mode;

        return $this;
    }

    
    public function getYMode(): ?string
    {
        return $this->yMode;
    }

    
    public function setYMode(?string $mode): static
    {
        $this->yMode = (string) $mode;

        return $this;
    }

    
    public function getXPosition(): null|float|int
    {
        return $this->xPos;
    }

    
    public function setXPosition(float $position): static
    {
        $this->xPos = $position;

        return $this;
    }

    
    public function getYPosition(): ?float
    {
        return $this->yPos;
    }

    
    public function setYPosition(float $position): static
    {
        $this->yPos = $position;

        return $this;
    }

    
    public function getWidth(): ?float
    {
        return $this->width;
    }

    
    public function setWidth(?float $width): static
    {
        $this->width = $width;

        return $this;
    }

    
    public function getHeight(): ?float
    {
        return $this->height;
    }

    
    public function setHeight(?float $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getShowLegendKey(): ?bool
    {
        return $this->showLegendKey;
    }

    
    public function setShowLegendKey(?bool $showLegendKey): self
    {
        $this->showLegendKey = $showLegendKey;

        return $this;
    }

    public function getShowVal(): ?bool
    {
        return $this->showVal;
    }

    
    public function setShowVal(?bool $showDataLabelValues): self
    {
        $this->showVal = $showDataLabelValues;

        return $this;
    }

    public function getShowCatName(): ?bool
    {
        return $this->showCatName;
    }

    
    public function setShowCatName(?bool $showCategoryName): self
    {
        $this->showCatName = $showCategoryName;

        return $this;
    }

    public function getShowSerName(): ?bool
    {
        return $this->showSerName;
    }

    
    public function setShowSerName(?bool $showSeriesName): self
    {
        $this->showSerName = $showSeriesName;

        return $this;
    }

    public function getShowPercent(): ?bool
    {
        return $this->showPercent;
    }

    
    public function setShowPercent(?bool $showPercentage): self
    {
        $this->showPercent = $showPercentage;

        return $this;
    }

    public function getShowBubbleSize(): ?bool
    {
        return $this->showBubbleSize;
    }

    
    public function setShowBubbleSize(?bool $showBubbleSize): self
    {
        $this->showBubbleSize = $showBubbleSize;

        return $this;
    }

    public function getShowLeaderLines(): ?bool
    {
        return $this->showLeaderLines;
    }

    
    public function setShowLeaderLines(?bool $showLeaderLines): self
    {
        $this->showLeaderLines = $showLeaderLines;

        return $this;
    }

    public function getLabelFillColor(): ?ChartColor
    {
        return $this->labelFillColor;
    }

    public function setLabelFillColor(?ChartColor $chartColor): self
    {
        $this->labelFillColor = $chartColor;

        return $this;
    }

    public function getLabelBorderColor(): ?ChartColor
    {
        return $this->labelBorderColor;
    }

    public function setLabelBorderColor(?ChartColor $chartColor): self
    {
        $this->labelBorderColor = $chartColor;

        return $this;
    }

    public function getLabelFont(): ?Font
    {
        return $this->labelFont;
    }

    public function setLabelFont(?Font $labelFont): self
    {
        $this->labelFont = $labelFont;

        return $this;
    }

    public function getLabelEffects(): ?Properties
    {
        return $this->labelEffects;
    }

    public function getLabelFontColor(): ?ChartColor
    {
        if ($this->labelFont === null) {
            return null;
        }

        return $this->labelFont->getChartColor();
    }

    public function setLabelFontColor(?ChartColor $chartColor): self
    {
        if ($this->labelFont === null) {
            $this->labelFont = new Font();
            $this->labelFont->setSize(null, true);
        }
        $this->labelFont->setChartColorFromObject($chartColor);

        return $this;
    }

    public function getDLblPos(): string
    {
        return $this->dLblPos;
    }

    public function setDLblPos(string $dLblPos): self
    {
        $this->dLblPos = $dLblPos;

        return $this;
    }

    public function getNumFmtCode(): string
    {
        return $this->numFmtCode;
    }

    public function setNumFmtCode(string $numFmtCode): self
    {
        $this->numFmtCode = $numFmtCode;

        return $this;
    }

    public function getNumFmtLinked(): bool
    {
        return $this->numFmtLinked;
    }

    public function setNumFmtLinked(bool $numFmtLinked): self
    {
        $this->numFmtLinked = $numFmtLinked;

        return $this;
    }

    
    public function __clone()
    {
        $this->labelFillColor = ($this->labelFillColor === null) ? null : clone $this->labelFillColor;
        $this->labelBorderColor = ($this->labelBorderColor === null) ? null : clone $this->labelBorderColor;
        $this->labelFont = ($this->labelFont === null) ? null : clone $this->labelFont;
        $this->labelEffects = ($this->labelEffects === null) ? null : clone $this->labelEffects;
    }
}
