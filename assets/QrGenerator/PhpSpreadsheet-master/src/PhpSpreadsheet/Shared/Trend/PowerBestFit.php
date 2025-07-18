<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

class PowerBestFit extends BestFit
{
    
    protected string $bestFitType = 'power';

    
    public function getValueOfYForX(float $xValue): float
    {
        return $this->getIntersect() * ($xValue - $this->xOffset) ** $this->getSlope();
    }

    
    public function getValueOfXForY(float $yValue): float
    {
        return (($yValue + $this->yOffset) / $this->getIntersect()) ** (1 / $this->getSlope());
    }

    
    public function getEquation(int $dp = 0): string
    {
        $slope = $this->getSlope($dp);
        $intersect = $this->getIntersect($dp);

        return 'Y = ' . $intersect . ' * X^' . $slope;
    }

    
    public function getIntersect(int $dp = 0): float
    {
        if ($dp != 0) {
            return round(exp($this->intersect), $dp);
        }

        return exp($this->intersect);
    }

    
    private function powerRegression(array $yValues, array $xValues, bool $const): void
    {
        $adjustedYValues = array_map(
            fn ($value): float => ($value < 0.0) ? 0 - log(abs($value)) : log($value),
            $yValues
        );
        $adjustedXValues = array_map(
            fn ($value): float => ($value < 0.0) ? 0 - log(abs($value)) : log($value),
            $xValues
        );

        $this->leastSquareFit($adjustedYValues, $adjustedXValues, $const);
    }

    
    public function __construct(array $yValues, array $xValues = [], bool $const = true)
    {
        parent::__construct($yValues, $xValues);

        if (!$this->error) {
            $this->powerRegression($yValues, $xValues, (bool) $const);
        }
    }
}
