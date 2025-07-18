<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class NewtonRaphson
{
    private const MAX_ITERATIONS = 256;

    
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function execute(float $probability): string|int|float
    {
        $xLo = 100;
        $xHi = 0;

        $dx = 1;
        $x = $xNew = 1;
        $i = 0;

        while ((abs($dx) > Functions::PRECISION) && ($i++ < self::MAX_ITERATIONS)) {
            
            $result = call_user_func($this->callback, $x);
            $error = $result - $probability;

            if ($error == 0.0) {
                $dx = 0;
            } elseif ($error < 0.0) {
                $xLo = $x;
            } else {
                $xHi = $x;
            }

            
            if ($result != 0.0) {
                $dx = $error / $result;
                $xNew = $x - $dx;
            }

            
            
            
            if (($xNew < $xLo) || ($xNew > $xHi) || ($result == 0.0)) {
                $xNew = ($xLo + $xHi) / 2;
                $dx = $xNew - $x;
            }
            $x = $xNew;
        }

        if ($i == self::MAX_ITERATIONS) {
            return ExcelError::NA();
        }

        return $x;
    }
}
