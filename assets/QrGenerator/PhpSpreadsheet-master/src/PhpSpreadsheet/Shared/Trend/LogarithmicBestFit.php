<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

class LogarithmicBestFit extends BestFit
{
    
    protected string $bestFitType = 'logarithmic';

    
    public function getValueOfYForX(float $xValue): float
    {
        return $this->getIntersect() + $this->getSlope() * log($xValue - $this->xOffset);
    }

    
    public function getValueOfXForY(float $yValue): float
    {
        return exp(($yValue - $this->getIntersect()) / $this->getSlope());
    }

    
    public function getEquation(int $dp = 0): string
    {
        $slope = $this->getSlope($dp);
        $intersect = $this->getIntersect($dp);

        return 'Y = ' . $slope . ' * log(' . $intersect . ' * X)';
    }

    
    private function logarithmicRegression(array $yValues, array $xValues, bool $const): void
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
            $this->logarithmicRegression($yValues, $xValues, (bool) $const);
        }
    }
}
