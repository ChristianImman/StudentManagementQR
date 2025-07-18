<?php

namespace PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;


class Expression extends WizardAbstract implements WizardInterface
{
    protected string $expression;

    public function __construct(string $cellRange)
    {
        parent::__construct($cellRange);
    }

    public function expression(string $expression): self
    {
        $expression = $this->validateOperand($expression, Wizard::VALUE_TYPE_FORMULA);
        $this->expression = $expression;

        return $this;
    }

    public function getConditional(): Conditional
    {
        $expression = $this->adjustConditionsForCellReferences([$this->expression]);

        $conditional = new Conditional();
        $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
        $conditional->setConditions($expression);
        $conditional->setStyle($this->getStyle());
        $conditional->setStopIfTrue($this->getStopIfTrue());

        return $conditional;
    }

    public static function fromConditional(Conditional $conditional, string $cellRange = 'A1'): WizardInterface
    {
        if ($conditional->getConditionType() !== Conditional::CONDITION_EXPRESSION) {
            throw new Exception('Conditional is not an Expression CF Rule conditional');
        }

        $wizard = new self($cellRange);
        $wizard->style = $conditional->getStyle();
        $wizard->stopIfTrue = $conditional->getStopIfTrue();
        $wizard->expression = self::reverseAdjustCellRef((string) ($conditional->getConditions()[0]), $cellRange);

        return $wizard;
    }

    
    public function __call(string $methodName, array $arguments): self
    {
        if ($methodName !== 'formula') {
            throw new Exception('Invalid Operation for Expression CF Rule Wizard');
        }

        $this->expression(...$arguments);

        return $this;
    }
}
