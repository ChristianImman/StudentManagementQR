<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

abstract class BestFit
{
    
    protected bool $error = false;

    
    protected string $bestFitType = 'undetermined';

    
    protected int $valueCount;

    
    protected array $xValues = [];

    
    protected array $yValues = [];

    
    protected bool $adjustToZero = false;

    
    protected array $yBestFitValues = [];

    protected float $goodnessOfFit = 1;

    protected float $stdevOfResiduals = 0;

    protected float $covariance = 0;

    protected float $correlation = 0;

    protected float $SSRegression = 0;

    protected float $SSResiduals = 0;

    protected float $DFResiduals = 0;

    protected float $f = 0;

    protected float $slope = 0;

    protected float $slopeSE = 0;

    protected float $intersect = 0;

    protected float $intersectSE = 0;

    protected float $xOffset = 0;

    protected float $yOffset = 0;

    public function getError(): bool
    {
        return $this->error;
    }

    public function getBestFitType(): string
    {
        return $this->bestFitType;
    }

    
    abstract public function getValueOfYForX(float $xValue): float;

    
    abstract public function getValueOfXForY(float $yValue): float;

    
    public function getXValues(): array
    {
        return $this->xValues;
    }

    
    abstract public function getEquation(int $dp = 0): string;

    
    public function getSlope(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->slope, $dp);
        }

        return $this->slope;
    }

    
    public function getSlopeSE(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->slopeSE, $dp);
        }

        return $this->slopeSE;
    }

    
    public function getIntersect(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->intersect, $dp);
        }

        return $this->intersect;
    }

    
    public function getIntersectSE(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->intersectSE, $dp);
        }

        return $this->intersectSE;
    }

    
    public function getGoodnessOfFit(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->goodnessOfFit, $dp);
        }

        return $this->goodnessOfFit;
    }

    
    public function getGoodnessOfFitPercent(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->goodnessOfFit * 100, $dp);
        }

        return $this->goodnessOfFit * 100;
    }

    
    public function getStdevOfResiduals(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->stdevOfResiduals, $dp);
        }

        return $this->stdevOfResiduals;
    }

    
    public function getSSRegression(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->SSRegression, $dp);
        }

        return $this->SSRegression;
    }

    
    public function getSSResiduals(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->SSResiduals, $dp);
        }

        return $this->SSResiduals;
    }

    
    public function getDFResiduals(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->DFResiduals, $dp);
        }

        return $this->DFResiduals;
    }

    
    public function getF(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->f, $dp);
        }

        return $this->f;
    }

    
    public function getCovariance(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->covariance, $dp);
        }

        return $this->covariance;
    }

    
    public function getCorrelation(int $dp = 0): float
    {
        if ($dp != 0) {
            return round($this->correlation, $dp);
        }

        return $this->correlation;
    }

    
    public function getYBestFitValues(): array
    {
        return $this->yBestFitValues;
    }

    protected function calculateGoodnessOfFit(float $sumX, float $sumY, float $sumX2, float $sumY2, float $sumXY, float $meanX, float $meanY, bool|int $const): void
    {
        $SSres = $SScov = $SStot = $SSsex = 0.0;
        foreach ($this->xValues as $xKey => $xValue) {
            $bestFitY = $this->yBestFitValues[$xKey] = $this->getValueOfYForX($xValue);

            $SSres += ($this->yValues[$xKey] - $bestFitY) * ($this->yValues[$xKey] - $bestFitY);
            if ($const === true) {
                $SStot += ($this->yValues[$xKey] - $meanY) * ($this->yValues[$xKey] - $meanY);
            } else {
                $SStot += $this->yValues[$xKey] * $this->yValues[$xKey];
            }
            $SScov += ($this->xValues[$xKey] - $meanX) * ($this->yValues[$xKey] - $meanY);
            if ($const === true) {
                $SSsex += ($this->xValues[$xKey] - $meanX) * ($this->xValues[$xKey] - $meanX);
            } else {
                $SSsex += $this->xValues[$xKey] * $this->xValues[$xKey];
            }
        }

        $this->SSResiduals = $SSres;
        $this->DFResiduals = $this->valueCount - 1 - ($const === true ? 1 : 0);

        if ($this->DFResiduals == 0.0) {
            $this->stdevOfResiduals = 0.0;
        } else {
            $this->stdevOfResiduals = sqrt($SSres / $this->DFResiduals);
        }

        if ($SStot == 0.0 || $SSres == $SStot) {
            $this->goodnessOfFit = 1;
        } else {
            $this->goodnessOfFit = 1 - ($SSres / $SStot);
        }

        $this->SSRegression = $this->goodnessOfFit * $SStot;
        $this->covariance = $SScov / $this->valueCount;
        $this->correlation = ($this->valueCount * $sumXY - $sumX * $sumY) / sqrt(($this->valueCount * $sumX2 - $sumX ** 2) * ($this->valueCount * $sumY2 - $sumY ** 2));
        $this->slopeSE = $this->stdevOfResiduals / sqrt($SSsex);
        $this->intersectSE = $this->stdevOfResiduals * sqrt(1 / ($this->valueCount - ($sumX * $sumX) / $sumX2));
        if ($this->SSResiduals != 0.0) {
            if ($this->DFResiduals == 0.0) {
                $this->f = 0.0;
            } else {
                $this->f = $this->SSRegression / ($this->SSResiduals / $this->DFResiduals);
            }
        } else {
            if ($this->DFResiduals == 0.0) {
                $this->f = 0.0;
            } else {
                $this->f = $this->SSRegression / $this->DFResiduals;
            }
        }
    }

    
    private function sumSquares(array $values)
    {
        return array_sum(
            array_map(
                fn ($value): float|int => $value ** 2,
                $values
            )
        );
    }

    
    protected function leastSquareFit(array $yValues, array $xValues, bool $const): void
    {
        
        $sumValuesX = array_sum($xValues);
        $sumValuesY = array_sum($yValues);
        $meanValueX = $sumValuesX / $this->valueCount;
        $meanValueY = $sumValuesY / $this->valueCount;
        $sumSquaresX = $this->sumSquares($xValues);
        $sumSquaresY = $this->sumSquares($yValues);
        $mBase = $mDivisor = 0.0;
        $xy_sum = 0.0;
        for ($i = 0; $i < $this->valueCount; ++$i) {
            $xy_sum += $xValues[$i] * $yValues[$i];

            if ($const === true) {
                $mBase += ($xValues[$i] - $meanValueX) * ($yValues[$i] - $meanValueY);
                $mDivisor += ($xValues[$i] - $meanValueX) * ($xValues[$i] - $meanValueX);
            } else {
                $mBase += $xValues[$i] * $yValues[$i];
                $mDivisor += $xValues[$i] * $xValues[$i];
            }
        }

        
        $this->slope = $mBase / $mDivisor;

        
        $this->intersect = ($const === true) ? $meanValueY - ($this->slope * $meanValueX) : 0.0;

        $this->calculateGoodnessOfFit($sumValuesX, $sumValuesY, $sumSquaresX, $sumSquaresY, $xy_sum, $meanValueX, $meanValueY, $const);
    }

    
    public function __construct(array $yValues, array $xValues = [])
    {
        
        $yValueCount = count($yValues);
        $xValueCount = count($xValues);

        
        if ($xValueCount === 0) {
            $xValues = range(1, $yValueCount);
        } elseif ($yValueCount !== $xValueCount) {
            
            $this->error = true;
        }

        $this->valueCount = $yValueCount;
        $this->xValues = $xValues;
        $this->yValues = $yValues;
    }
}
