<?php

namespace PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Conditional;


class Duplicates extends WizardAbstract implements WizardInterface
{
    protected const OPERATORS = [
        'duplicates' => false,
        'unique' => true,
    ];

    protected bool $inverse;

    public function __construct(string $cellRange, bool $inverse = false)
    {
        parent::__construct($cellRange);
        $this->inverse = $inverse;
    }

    protected function inverse(bool $inverse): void
    {
        $this->inverse = $inverse;
    }

    public function getConditional(): Conditional
    {
        $conditional = new Conditional();
        $conditional->setConditionType(
            $this->inverse ? Conditional::CONDITION_UNIQUE : Conditional::CONDITION_DUPLICATES
        );
        $conditional->setStyle($this->getStyle());
        $conditional->setStopIfTrue($this->getStopIfTrue());

        return $conditional;
    }

    public static function fromConditional(Conditional $conditional, string $cellRange = 'A1'): WizardInterface
    {
        if (
            $conditional->getConditionType() !== Conditional::CONDITION_DUPLICATES
            && $conditional->getConditionType() !== Conditional::CONDITION_UNIQUE
        ) {
            throw new Exception('Conditional is not a Duplicates CF Rule conditional');
        }

        $wizard = new self($cellRange);
        $wizard->style = $conditional->getStyle();
        $wizard->stopIfTrue = $conditional->getStopIfTrue();
        $wizard->inverse = $conditional->getConditionType() === Conditional::CONDITION_UNIQUE;

        return $wizard;
    }

    
    public function __call(string $methodName, array $arguments): self
    {
        if (!array_key_exists($methodName, self::OPERATORS)) {
            throw new Exception('Invalid Operation for Errors CF Rule Wizard');
        }

        $this->inverse(self::OPERATORS[$methodName]);

        return $this;
    }
}
