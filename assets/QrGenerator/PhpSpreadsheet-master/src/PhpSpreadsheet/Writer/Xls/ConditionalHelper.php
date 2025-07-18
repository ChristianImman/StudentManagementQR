<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;

class ConditionalHelper
{
    
    protected Parser $parser;

    protected mixed $condition;

    protected string $cellRange;

    protected ?string $tokens = null;

    protected int $size;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function processCondition(mixed $condition, string $cellRange): void
    {
        $this->condition = $condition;
        $this->cellRange = $cellRange;

        if (is_int($condition) && $condition >= 0 && $condition <= 65535) {
            $this->size = 3;
            $this->tokens = pack('Cv', 0x1E, $condition);
        } else {
            try {
                $formula = Wizard\WizardAbstract::reverseAdjustCellRef(StringHelper::convertToString($condition), $cellRange);
                $this->parser->parse($formula);
                $this->tokens = $this->parser->toReversePolish();
                $this->size = strlen($this->tokens ?? '');
            } catch (PhpSpreadsheetException) {
                
                $this->tokens = pack('Cv', 0x1E, 0);
                $this->size = 3;
            }
        }
    }

    public function tokens(): ?string
    {
        return $this->tokens;
    }

    public function size(): int
    {
        return $this->size;
    }
}
