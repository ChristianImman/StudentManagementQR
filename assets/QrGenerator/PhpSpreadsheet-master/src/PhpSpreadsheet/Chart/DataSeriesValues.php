<?php

namespace PhpOffice\PhpSpreadsheet\Chart;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataSeriesValues extends Properties
{
    const DATASERIES_TYPE_STRING = 'String';
    const DATASERIES_TYPE_NUMBER = 'Number';

    private const DATA_TYPE_VALUES = [
        self::DATASERIES_TYPE_STRING,
        self::DATASERIES_TYPE_NUMBER,
    ];

    
    private string $dataType;

    
    private ?string $dataSource;

    
    private ?string $formatCode;

    
    private ?string $pointMarker;

    private ChartColor $markerFillColor;

    private ChartColor $markerBorderColor;

    
    private int $pointSize = 3;

    
    private int $pointCount;

    
    private ?array $dataValues;

    
    private $fillColor;

    private bool $scatterLines = true;

    private bool $bubble3D = false;

    private ?Layout $labelLayout = null;

    
    private array $trendLines = [];

    
    public function __construct(
        string $dataType = self::DATASERIES_TYPE_NUMBER,
        ?string $dataSource = null,
        ?string $formatCode = null,
        int $pointCount = 0,
        ?array $dataValues = [],
        ?string $marker = null,
        null|ChartColor|array|string $fillColor = null,
        int|string $pointSize = 3
    ) {
        parent::__construct();
        $this->markerFillColor = new ChartColor();
        $this->markerBorderColor = new ChartColor();
        $this->setDataType($dataType);
        $this->dataSource = $dataSource;
        $this->formatCode = $formatCode;
        $this->pointCount = $pointCount;
        $this->dataValues = $dataValues;
        $this->pointMarker = $marker;
        if ($fillColor !== null) {
            $this->setFillColor($fillColor);
        }
        if (is_numeric($pointSize)) {
            $this->pointSize = (int) $pointSize;
        }
    }

    
    public function getDataType(): string
    {
        return $this->dataType;
    }

    
    public function setDataType(string $dataType): static
    {
        if (!in_array($dataType, self::DATA_TYPE_VALUES)) {
            throw new Exception('Invalid datatype for chart data series values');
        }
        $this->dataType = $dataType;

        return $this;
    }

    
    public function getDataSource(): ?string
    {
        return $this->dataSource;
    }

    
    public function setDataSource(?string $dataSource): static
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    
    public function getPointMarker(): ?string
    {
        return $this->pointMarker;
    }

    
    public function setPointMarker(string $marker): static
    {
        $this->pointMarker = $marker;

        return $this;
    }

    public function getMarkerFillColor(): ChartColor
    {
        return $this->markerFillColor;
    }

    public function getMarkerBorderColor(): ChartColor
    {
        return $this->markerBorderColor;
    }

    
    public function getPointSize(): int
    {
        return $this->pointSize;
    }

    
    public function setPointSize(int $size = 3): static
    {
        $this->pointSize = $size;

        return $this;
    }

    
    public function getFormatCode(): ?string
    {
        return $this->formatCode;
    }

    
    public function setFormatCode(string $formatCode): static
    {
        $this->formatCode = $formatCode;

        return $this;
    }

    
    public function getPointCount(): int
    {
        return $this->pointCount;
    }

    
    public function getFillColorObject()
    {
        return $this->fillColor;
    }

    private function stringToChartColor(string $fillString): ChartColor
    {
        $value = $type = '';
        if (str_starts_with($fillString, '*')) {
            $type = 'schemeClr';
            $value = substr($fillString, 1);
        } elseif (str_starts_with($fillString, '/')) {
            $type = 'prstClr';
            $value = substr($fillString, 1);
        } elseif ($fillString !== '') {
            $type = 'srgbClr';
            $value = $fillString;
            $this->validateColor($value);
        }

        return new ChartColor($value, null, $type);
    }

    private function chartColorToString(ChartColor $chartColor): string
    {
        $type = (string) $chartColor->getColorProperty('type');
        $value = (string) $chartColor->getColorProperty('value');
        if ($type === '' || $value === '') {
            return '';
        }
        if ($type === 'schemeClr') {
            return "*$value";
        }
        if ($type === 'prstClr') {
            return "/$value";
        }

        return $value;
    }

    
    public function getFillColor(): string|array
    {
        if ($this->fillColor === null) {
            return '';
        }
        if (is_array($this->fillColor)) {
            $array = [];
            foreach ($this->fillColor as $chartColor) {
                $array[] = $this->chartColorToString($chartColor);
            }

            return $array;
        }

        return $this->chartColorToString($this->fillColor);
    }

    
    public function setFillColor($color): static
    {
        if (is_array($color)) {
            $this->fillColor = [];
            foreach ($color as $fillString) {
                if ($fillString instanceof ChartColor) {
                    $this->fillColor[] = $fillString;
                } else {
                    $this->fillColor[] = $this->stringToChartColor($fillString);
                }
            }
        } elseif ($color instanceof ChartColor) {
            $this->fillColor = $color;
        } else {
            $this->fillColor = $this->stringToChartColor($color);
        }

        return $this;
    }

    
    private function validateColor(string $color): bool
    {
        if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
            throw new Exception(sprintf('Invalid hex color for chart series (color: "%s")', $color));
        }

        return true;
    }

    
    public function getLineWidth(): null|float|int
    {
        return $this->lineStyleProperties['width'];
    }

    
    public function setLineWidth(null|float|int $width): static
    {
        $this->lineStyleProperties['width'] = $width;

        return $this;
    }

    
    public function isMultiLevelSeries(): ?bool
    {
        if (!empty($this->dataValues)) {
            return is_array(array_values($this->dataValues)[0]);
        }

        return null;
    }

    
    public function multiLevelCount(): int
    {
        $levelCount = 0;
        foreach (($this->dataValues ?? []) as $dataValueSet) {
            $levelCount = max($levelCount, count($dataValueSet));
        }

        return $levelCount;
    }

    
    public function getDataValues(): ?array
    {
        return $this->dataValues;
    }

    
    public function getDataValue(): mixed
    {
        if ($this->dataValues === null) {
            return null;
        }
        $count = count($this->dataValues);
        if ($count == 0) {
            return null;
        } elseif ($count == 1) {
            return $this->dataValues[0];
        }

        return $this->dataValues;
    }

    
    public function setDataValues(array $dataValues): static
    {
        $this->dataValues = Functions::flattenArray($dataValues);
        $this->pointCount = count($dataValues);

        return $this;
    }

    public function refresh(Worksheet $worksheet, bool $flatten = true): void
    {
        if ($this->dataSource !== null) {
            $calcEngine = Calculation::getInstance($worksheet->getParent());
            $newDataValues = Calculation::unwrapResult(
                $calcEngine->_calculateFormulaValue(
                    '=' . $this->dataSource,
                    null,
                    $worksheet->getCell('A1')
                )
            );
            if ($flatten) {
                $this->dataValues = Functions::flattenArray($newDataValues);
                foreach ($this->dataValues as &$dataValue) {
                    if (is_string($dataValue) && !empty($dataValue) && $dataValue[0] == '
                        $dataValue = 0.0;
                    }
                }
                unset($dataValue);
            } else {
                [, $cellRange] = Worksheet::extractSheetTitle($this->dataSource, true);
                $dimensions = Coordinate::rangeDimension(str_replace('$', '', $cellRange ?? ''));
                if (($dimensions[0] == 1) || ($dimensions[1] == 1)) {
                    $this->dataValues = Functions::flattenArray($newDataValues);
                } else {
                    
                    $newDataValuesx = $newDataValues;
                    $newArray = array_values(array_shift($newDataValuesx) ?? []);
                    foreach ($newArray as $i => $newDataSet) {
                        $newArray[$i] = [$newDataSet];
                    }

                    foreach ($newDataValuesx as $newDataSet) {
                        $i = 0;
                        foreach ($newDataSet as $newDataVal) {
                            array_unshift($newArray[$i++], $newDataVal);
                        }
                    }
                    $this->dataValues = $newArray;
                }
            }
            $this->pointCount = count($this->dataValues);
        }
    }

    public function getScatterLines(): bool
    {
        return $this->scatterLines;
    }

    public function setScatterLines(bool $scatterLines): self
    {
        $this->scatterLines = $scatterLines;

        return $this;
    }

    public function getBubble3D(): bool
    {
        return $this->bubble3D;
    }

    public function setBubble3D(bool $bubble3D): self
    {
        $this->bubble3D = $bubble3D;

        return $this;
    }

    
    private bool $smoothLine = false;

    
    public function getSmoothLine(): bool
    {
        return $this->smoothLine;
    }

    
    public function setSmoothLine(bool $smoothLine): static
    {
        $this->smoothLine = $smoothLine;

        return $this;
    }

    public function getLabelLayout(): ?Layout
    {
        return $this->labelLayout;
    }

    public function setLabelLayout(?Layout $labelLayout): self
    {
        $this->labelLayout = $labelLayout;

        return $this;
    }

    
    public function setTrendLines(array $trendLines): self
    {
        $this->trendLines = $trendLines;

        return $this;
    }

    
    public function getTrendLines(): array
    {
        return $this->trendLines;
    }

    
    public function __clone()
    {
        parent::__clone();
        $this->markerFillColor = clone $this->markerFillColor;
        $this->markerBorderColor = clone $this->markerBorderColor;
        if (is_array($this->fillColor)) {
            $fillColor = $this->fillColor;
            $this->fillColor = [];
            foreach ($fillColor as $color) {
                $this->fillColor[] = clone $color;
            }
        } elseif ($this->fillColor instanceof ChartColor) {
            $this->fillColor = clone $this->fillColor;
        }
        $this->labelLayout = ($this->labelLayout === null) ? null : clone $this->labelLayout;
        $trendLines = $this->trendLines;
        $this->trendLines = [];
        foreach ($trendLines as $trendLine) {
            $this->trendLines[] = clone $trendLine;
        }
    }
}
