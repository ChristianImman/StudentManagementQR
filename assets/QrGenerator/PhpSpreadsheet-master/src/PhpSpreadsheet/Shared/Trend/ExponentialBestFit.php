<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

class ExponentialBestFit extends BestFit
{
    
    protected string $bestFitType = 'exponential';

    
    public function getValueOfYForX(float $xValue): float
    {
        return $this->getIntersect() * $this->getSlope() ** ($xValue - $this->xOffset);
    }

    
    public function getValueOfXForY(float $yValue): float
    {
        return log(($yValue + $this->yOffset) / $this->getIntersect()) / log($this->getSlope());
    }

    
    public function getEquation(int $dp = 0): string
    {
        $slope = $this->getSlope($dp);
        $intersect = $this->getIntersect($dp);

        return 'Y = ' . $intersect . ' * ' . $slope . '^X';
    }

    
    public function getSlope(int $dp = 0): float
    {
        if ($dp != 0) {
            return round(exp($this->slope), $dp);
        }

        return exp($this->slope);
    }

    
    public function getIntersect(int $dp = 0): float
    {
        if ($dp != 0) {
            return round(exp($this->intersect), $dp);
        }

        return exp($this->intersect);
    }

    
    private function exponentialRegression(array $yValues, array $xValues, bool $const): void
    {
        $adjustedYValues = array_map(
            fn ($value): float => ($value < 0.0) ? 0 - log(abs($value)) : log($value),
            $yValues
        );

        $this->leastSquareFit($adjustedYValues, $xValues, $const);
    }

    
    public function __construct(array $yValues, array $xValues = [], bool $const = true)
    {
        parent::__construct($yValues, $xValues);

        if (!$this->error) {
            $this->exponentialRegression($yValues, $xValues, (bool) $const);
        }
    }
}
