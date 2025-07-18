<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

use Matrix\Matrix;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;







class PolynomialBestFit extends BestFit
{
    
    protected string $bestFitType = 'polynomial';

    
    protected int $order = 0;

    private bool $implemented = false;

    
    public function getOrder(): int
    {
        return $this->order;
    }

    
    public function getValueOfYForX(float $xValue): float
    {
        $retVal = $this->getIntersect();
        $slope = $this->getSlope();
        
        
        foreach ($slope as $key => $value) {
            if ($value != 0.0) {
                $retVal += $value * $xValue ** ($key + 1);
            }
        }

        return $retVal;
    }

    
    public function getValueOfXForY(float $yValue): float
    {
        return ($yValue - $this->getIntersect()) / $this->getSlope();
    }

    
    public function getEquation(int $dp = 0): string
    {
        $slope = $this->getSlope($dp);
        $intersect = $this->getIntersect($dp);

        $equation = 'Y = ' . $intersect;
        
        
        foreach ($slope as $key => $value) {
            if ($value != 0.0) {
                $equation .= ' + ' . $value . ' * X';
                if ($key > 0) {
                    $equation .= '^' . ($key + 1);
                }
            }
        }

        return $equation;
    }

    
    public function getSlope(int $dp = 0): float
    {
        if ($dp != 0) {
            $coefficients = [];
            /
    private function polynomialRegression(int $order, array $yValues, array $xValues): void
    {
        
        $x_sum = array_sum($xValues);
        $y_sum = array_sum($yValues);
        $xx_sum = $xy_sum = $yy_sum = 0;
        for ($i = 0; $i < $this->valueCount; ++$i) {
            $xy_sum += $xValues[$i] * $yValues[$i];
            $xx_sum += $xValues[$i] * $xValues[$i];
            $yy_sum += $yValues[$i] * $yValues[$i];
        }
        
        $A = [];
        $B = [];
        for ($i = 0; $i < $this->valueCount; ++$i) {
            for ($j = 0; $j <= $order; ++$j) {
                $A[$i][$j] = $xValues[$i] ** $j;
            }
        }
        for ($i = 0; $i < $this->valueCount; ++$i) {
            $B[$i] = [$yValues[$i]];
        }
        $matrixA = new Matrix($A);
        $matrixB = new Matrix($B);
        $C = $matrixA->solve($matrixB);

        $coefficients = [];
        for ($i = 0; $i < $C->rows; ++$i) {
            $r = $C->getValue($i + 1, 1); 
            if (!is_numeric($r) || abs($r + 0) <= 10 ** (-9)) {
                $r = 0;
            } else {
                $r += 0;
            }
            $coefficients[] = $r;
        }

        $this->intersect = (float) array_shift($coefficients);
        
        /
    public function __construct(int $order, array $yValues, array $xValues = [])
    {
        if (!$this->implemented) {
            throw new SpreadsheetException('Polynomial Best Fit not yet implemented');
        }

        parent::__construct($yValues, $xValues);

        if (!$this->error) {
            if ($order < $this->valueCount) {
                $this->bestFitType .= '_' . $order;
                $this->order = $order;
                $this->polynomialRegression($order, $yValues, $xValues);
                if (($this->getGoodnessOfFit() < 0.0) || ($this->getGoodnessOfFit() > 1.0)) {
                    $this->error = true;
                }
            } else {
                $this->error = true;
            }
        }
    }
}
