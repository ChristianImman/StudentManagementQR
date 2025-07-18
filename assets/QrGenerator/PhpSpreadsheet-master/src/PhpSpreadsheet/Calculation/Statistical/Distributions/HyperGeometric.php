<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Combinations;

class HyperGeometric
{
    use ArrayEnabled;

    
    public static function distribution(mixed $sampleSuccesses, mixed $sampleNumber, mixed $populationSuccesses, mixed $populationNumber): array|string|float
    {
        if (
            is_array($sampleSuccesses) || is_array($sampleNumber)
            || is_array($populationSuccesses) || is_array($populationNumber)
        ) {
            return self::evaluateArrayArguments(
                [self::class, __FUNCTION__],
                $sampleSuccesses,
                $sampleNumber,
                $populationSuccesses,
                $populationNumber
            );
        }

        try {
            $sampleSuccesses = DistributionValidations::validateInt($sampleSuccesses);
            $sampleNumber = DistributionValidations::validateInt($sampleNumber);
            $populationSuccesses = DistributionValidations::validateInt($populationSuccesses);
            $populationNumber = DistributionValidations::validateInt($populationNumber);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($sampleSuccesses < 0) || ($sampleSuccesses > $sampleNumber) || ($sampleSuccesses > $populationSuccesses)) {
            return ExcelError::NAN();
        }
        if (($sampleNumber <= 0) || ($sampleNumber > $populationNumber)) {
            return ExcelError::NAN();
        }
        if (($populationSuccesses <= 0) || ($populationSuccesses > $populationNumber)) {
            return ExcelError::NAN();
        }

        $successesPopulationAndSample = (float) Combinations::withoutRepetition($populationSuccesses, $sampleSuccesses);
        $numbersPopulationAndSample = (float) Combinations::withoutRepetition($populationNumber, $sampleNumber);
        $adjustedPopulationAndSample = (float) Combinations::withoutRepetition(
            $populationNumber - $populationSuccesses,
            $sampleNumber - $sampleSuccesses
        );

        return $successesPopulationAndSample * $adjustedPopulationAndSample / $numbersPopulationAndSample;
    }
}
