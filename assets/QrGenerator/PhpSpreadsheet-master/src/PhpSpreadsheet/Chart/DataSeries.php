<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataSeries
{
    const TYPE_BARCHART = 'barChart';
    const TYPE_BARCHART_3D = 'bar3DChart';
    const TYPE_LINECHART = 'lineChart';
    const TYPE_LINECHART_3D = 'line3DChart';
    const TYPE_AREACHART = 'areaChart';
    const TYPE_AREACHART_3D = 'area3DChart';
    const TYPE_PIECHART = 'pieChart';
    const TYPE_PIECHART_3D = 'pie3DChart';
    const TYPE_DOUGHNUTCHART = 'doughnutChart';
    const TYPE_DONUTCHART = self::TYPE_DOUGHNUTCHART; 
    const TYPE_SCATTERCHART = 'scatterChart';
    const TYPE_SURFACECHART = 'surfaceChart';
    const TYPE_SURFACECHART_3D = 'surface3DChart';
    const TYPE_RADARCHART = 'radarChart';
    const TYPE_BUBBLECHART = 'bubbleChart';
    const TYPE_STOCKCHART = 'stockChart';
    const TYPE_CANDLECHART = self::TYPE_STOCKCHART; 

    const GROUPING_CLUSTERED = 'clustered';
    const GROUPING_STACKED = 'stacked';
    const GROUPING_PERCENT_STACKED = 'percentStacked';
    const GROUPING_STANDARD = 'standard';

    const DIRECTION_BAR = 'bar';
    const DIRECTION_HORIZONTAL = self::DIRECTION_BAR;
    const DIRECTION_COL = 'col';
    const DIRECTION_COLUMN = self::DIRECTION_COL;
    const DIRECTION_VERTICAL = self::DIRECTION_COL;

    const STYLE_LINEMARKER = 'lineMarker';
    const STYLE_SMOOTHMARKER = 'smoothMarker';
    const STYLE_MARKER = 'marker';
    const STYLE_FILLED = 'filled';

    const EMPTY_AS_GAP = 'gap';
    const EMPTY_AS_ZERO = 'zero';
    const EMPTY_AS_SPAN = 'span';
    const DEFAULT_EMPTY_AS = self::EMPTY_AS_GAP;
    const VALID_EMPTY_AS = [self::EMPTY_AS_GAP, self::EMPTY_AS_ZERO, self::EMPTY_AS_SPAN];

    
    private ?string $plotType;

    
    private ?string $plotGrouping;

    
    private string $plotDirection;

    
    private ?string $plotStyle;

    
    private array $plotOrder;

    
    private array $plotLabel;

    
    private array $plotCategory;

    
    private bool $smoothLine;

    
    private array $plotValues;

    
    private array $plotBubbleSizes = [];

    
    public function __construct(
        null|string $plotType = null,
        null|string $plotGrouping = null,
        array $plotOrder = [],
        array $plotLabel = [],
        array $plotCategory = [],
        array $plotValues = [],
        ?string $plotDirection = null,
        bool $smoothLine = false,
        ?string $plotStyle = null
    ) {
        $this->plotType = $plotType;
        $this->plotGrouping = $plotGrouping;
        $this->plotOrder = $plotOrder;
        $keys = array_keys($plotValues);
        $this->plotValues = $plotValues;
        if (!isset($plotLabel[$keys[0]])) {
            $plotLabel[$keys[0]] = new DataSeriesValues();
        }
        $this->plotLabel = $plotLabel;

        if (!isset($plotCategory[$keys[0]])) {
            $plotCategory[$keys[0]] = new DataSeriesValues();
        }
        $this->plotCategory = $plotCategory;

        $this->smoothLine = (bool) $smoothLine;
        $this->plotStyle = $plotStyle;

        if ($plotDirection === null) {
            $plotDirection = self::DIRECTION_COL;
        }
        $this->plotDirection = $plotDirection;
    }

    
    public function getPlotType(): ?string
    {
        return $this->plotType;
    }

    
    public function setPlotType(string $plotType): static
    {
        $this->plotType = $plotType;

        return $this;
    }

    
    public function getPlotGrouping(): ?string
    {
        return $this->plotGrouping;
    }

    
    public function setPlotGrouping(string $groupingType): static
    {
        $this->plotGrouping = $groupingType;

        return $this;
    }

    
    public function getPlotDirection(): string
    {
        return $this->plotDirection;
    }

    
    public function setPlotDirection(string $plotDirection): static
    {
        $this->plotDirection = $plotDirection;

        return $this;
    }

    
    public function getPlotOrder(): array
    {
        return $this->plotOrder;
    }

    
    public function getPlotLabels(): array
    {
        return $this->plotLabel;
    }

    
    public function getPlotLabelByIndex(int $index): bool|DataSeriesValues
    {
        $keys = array_keys($this->plotLabel);
        if (in_array($index, $keys)) {
            return $this->plotLabel[$index];
        }

        return false;
    }

    
    public function getPlotCategories(): array
    {
        return $this->plotCategory;
    }

    
    public function getPlotCategoryByIndex(int $index): bool|DataSeriesValues
    {
        $keys = array_keys($this->plotCategory);
        if (in_array($index, $keys)) {
            return $this->plotCategory[$index];
        } elseif (isset($keys[$index])) {
            return $this->plotCategory[$keys[$index]];
        }

        return false;
    }

    
    public function getPlotStyle(): ?string
    {
        return $this->plotStyle;
    }

    
    public function setPlotStyle(?string $plotStyle): static
    {
        $this->plotStyle = $plotStyle;

        return $this;
    }

    
    public function getPlotValues(): array
    {
        return $this->plotValues;
    }

    
    public function getPlotValuesByIndex(int $index): bool|DataSeriesValues
    {
        $keys = array_keys($this->plotValues);
        if (in_array($index, $keys)) {
            return $this->plotValues[$index];
        }

        return false;
    }

    
    public function getPlotBubbleSizes(): array
    {
        return $this->plotBubbleSizes;
    }

    
    public function setPlotBubbleSizes(array $plotBubbleSizes): self
    {
        $this->plotBubbleSizes = $plotBubbleSizes;

        return $this;
    }

    
    public function getPlotSeriesCount(): int
    {
        return count($this->plotValues);
    }

    
    public function getSmoothLine(): bool
    {
        return $this->smoothLine;
    }

    
    public function setSmoothLine(bool $smoothLine): static
    {
        $this->smoothLine = $smoothLine;

        return $this;
    }

    public function refresh(Worksheet $worksheet): void
    {
        foreach ($this->plotValues as $plotValues) {
            $plotValues->refresh($worksheet, true);
        }
        foreach ($this->plotLabel as $plotValues) {
            $plotValues->refresh($worksheet, true);
        }
        foreach ($this->plotCategory as $plotValues) {
            $plotValues->refresh($worksheet, false);
        }
    }

    
    public function __clone()
    {
        $plotLabels = $this->plotLabel;
        $this->plotLabel = [];
        foreach ($plotLabels as $plotLabel) {
            $this->plotLabel[] = $plotLabel;
        }
        $plotCategories = $this->plotCategory;
        $this->plotCategory = [];
        foreach ($plotCategories as $plotCategory) {
            $this->plotCategory[] = clone $plotCategory;
        }
        $plotValues = $this->plotValues;
        $this->plotValues = [];
        foreach ($plotValues as $plotValue) {
            $this->plotValues[] = clone $plotValue;
        }
        $plotBubbleSizes = $this->plotBubbleSizes;
        $this->plotBubbleSizes = [];
        foreach ($plotBubbleSizes as $plotBubbleSize) {
            $this->plotBubbleSizes[] = clone $plotBubbleSize;
        }
    }
}
