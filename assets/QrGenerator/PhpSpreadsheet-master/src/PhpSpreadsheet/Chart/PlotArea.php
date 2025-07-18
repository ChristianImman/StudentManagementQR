<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlotArea
{
    
    private bool $noFill = false;

    
    private array $gradientFillStops = [];

    
    private ?float $gradientFillAngle = null;

    
    private ?Layout $layout;

    
    private array $plotSeries;

    
    public function __construct(?Layout $layout = null, array $plotSeries = [])
    {
        $this->layout = $layout;
        $this->plotSeries = $plotSeries;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    
    public function getPlotGroupCount(): int
    {
        return count($this->plotSeries);
    }

    
    public function getPlotSeriesCount(): int|float
    {
        $seriesCount = 0;
        foreach ($this->plotSeries as $plot) {
            $seriesCount += $plot->getPlotSeriesCount();
        }

        return $seriesCount;
    }

    
    public function getPlotGroup(): array
    {
        return $this->plotSeries;
    }

    
    public function getPlotGroupByIndex(int $index): DataSeries
    {
        return $this->plotSeries[$index];
    }

    
    public function setPlotSeries(array $plotSeries): static
    {
        $this->plotSeries = $plotSeries;

        return $this;
    }

    public function refresh(Worksheet $worksheet): void
    {
        foreach ($this->plotSeries as $plotSeries) {
            $plotSeries->refresh($worksheet);
        }
    }

    public function setNoFill(bool $noFill): self
    {
        $this->noFill = $noFill;

        return $this;
    }

    public function getNoFill(): bool
    {
        return $this->noFill;
    }

    
    public function setGradientFillProperties(array $gradientFillStops, ?float $gradientFillAngle): self
    {
        $this->gradientFillStops = $gradientFillStops;
        $this->gradientFillAngle = $gradientFillAngle;

        return $this;
    }

    
    public function getGradientFillAngle(): ?float
    {
        return $this->gradientFillAngle;
    }

    
    public function getGradientFillStops(): array
    {
        return $this->gradientFillStops;
    }

    private ?int $gapWidth = null;

    private bool $useUpBars = false;

    private bool $useDownBars = false;

    public function getGapWidth(): ?int
    {
        return $this->gapWidth;
    }

    public function setGapWidth(?int $gapWidth): self
    {
        $this->gapWidth = $gapWidth;

        return $this;
    }

    public function getUseUpBars(): bool
    {
        return $this->useUpBars;
    }

    public function setUseUpBars(bool $useUpBars): self
    {
        $this->useUpBars = $useUpBars;

        return $this;
    }

    public function getUseDownBars(): bool
    {
        return $this->useDownBars;
    }

    public function setUseDownBars(bool $useDownBars): self
    {
        $this->useDownBars = $useDownBars;

        return $this;
    }

    
    public function __clone()
    {
        $this->layout = ($this->layout === null) ? null : clone $this->layout;
        $plotSeries = $this->plotSeries;
        $this->plotSeries = [];
        foreach ($plotSeries as $series) {
            $this->plotSeries[] = clone $series;
        }
    }
}
