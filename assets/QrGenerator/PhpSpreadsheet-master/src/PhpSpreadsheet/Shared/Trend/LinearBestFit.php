<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

class LinearBestFit extends BestFit
{
    
    protected string $bestFitType = 'linear';

    
    public function getValueOfYForX(float $xValue): float
    {
        return $this->getIntersect() + $this->getSlope() * $xValue;
    }

    
    public function getValueOfXForY(float $yValue): float
    {
        return ($yValue - $this->getIntersect()) / $this->getSlope();
    }

    
    public function getEquation(int $dp = 0): string
    {
        $slope = $this->getSlope($dp);
        $intersect = $this->getIntersect($dp);

        return 'Y = ' . $intersect . ' + ' . $slope . ' * X';
    }

    
    private function linearRegression(array $yValues, array $xValues, bool $const): void
    {
        $this->leastSquareFit($yValues, $xValues, $const);
    }

    
    public function __construct(array $yValues, array $xValues = [], bool $const = true)
    {
        parent::__construct($yValues, $xValues);

        if (!$this->error) {
            $this->linearRegression($yValues, $xValues, (bool) $const);
        }
    }
}
