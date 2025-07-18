<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

class DataValidation
{
    
    const TYPE_NONE = 'none';
    const TYPE_CUSTOM = 'custom';
    const TYPE_DATE = 'date';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_LIST = 'list';
    const TYPE_TEXTLENGTH = 'textLength';
    const TYPE_TIME = 'time';
    const TYPE_WHOLE = 'whole';

    
    const STYLE_STOP = 'stop';
    const STYLE_WARNING = 'warning';
    const STYLE_INFORMATION = 'information';

    
    const OPERATOR_BETWEEN = 'between';
    const OPERATOR_EQUAL = 'equal';
    const OPERATOR_GREATERTHAN = 'greaterThan';
    const OPERATOR_GREATERTHANOREQUAL = 'greaterThanOrEqual';
    const OPERATOR_LESSTHAN = 'lessThan';
    const OPERATOR_LESSTHANOREQUAL = 'lessThanOrEqual';
    const OPERATOR_NOTBETWEEN = 'notBetween';
    const OPERATOR_NOTEQUAL = 'notEqual';
    private const DEFAULT_OPERATOR = self::OPERATOR_BETWEEN;

    
    private string $formula1 = '';

    
    private string $formula2 = '';

    
    private string $type = self::TYPE_NONE;

    
    private string $errorStyle = self::STYLE_STOP;

    
    private string $operator = self::DEFAULT_OPERATOR;

    
    private bool $allowBlank = false;

    
    private bool $showDropDown = false;

    
    private bool $showInputMessage = false;

    
    private bool $showErrorMessage = false;

    
    private string $errorTitle = '';

    
    private string $error = '';

    
    private string $promptTitle = '';

    
    private string $prompt = '';

    
    public function getFormula1(): string
    {
        return $this->formula1;
    }

    
    public function setFormula1(string $formula): static
    {
        $this->formula1 = $formula;

        return $this;
    }

    
    public function getFormula2(): string
    {
        return $this->formula2;
    }

    
    public function setFormula2(string $formula): static
    {
        $this->formula2 = $formula;

        return $this;
    }

    
    public function getType(): string
    {
        return $this->type;
    }

    
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    
    public function getErrorStyle(): string
    {
        return $this->errorStyle;
    }

    
    public function setErrorStyle(string $errorStyle): static
    {
        $this->errorStyle = $errorStyle;

        return $this;
    }

    
    public function getOperator(): string
    {
        return $this->operator;
    }

    
    public function setOperator(string $operator): static
    {
        $this->operator = ($operator === '') ? self::DEFAULT_OPERATOR : $operator;

        return $this;
    }

    
    public function getAllowBlank(): bool
    {
        return $this->allowBlank;
    }

    
    public function setAllowBlank(bool $allowBlank): static
    {
        $this->allowBlank = $allowBlank;

        return $this;
    }

    
    public function getShowDropDown(): bool
    {
        return $this->showDropDown;
    }

    
    public function setShowDropDown(bool $showDropDown): static
    {
        $this->showDropDown = $showDropDown;

        return $this;
    }

    
    public function getShowInputMessage(): bool
    {
        return $this->showInputMessage;
    }

    
    public function setShowInputMessage(bool $showInputMessage): static
    {
        $this->showInputMessage = $showInputMessage;

        return $this;
    }

    
    public function getShowErrorMessage(): bool
    {
        return $this->showErrorMessage;
    }

    
    public function setShowErrorMessage(bool $showErrorMessage): static
    {
        $this->showErrorMessage = $showErrorMessage;

        return $this;
    }

    
    public function getErrorTitle(): string
    {
        return $this->errorTitle;
    }

    
    public function setErrorTitle(string $errorTitle): static
    {
        $this->errorTitle = $errorTitle;

        return $this;
    }

    
    public function getError(): string
    {
        return $this->error;
    }

    
    public function setError(string $error): static
    {
        $this->error = $error;

        return $this;
    }

    
    public function getPromptTitle(): string
    {
        return $this->promptTitle;
    }

    
    public function setPromptTitle(string $promptTitle): static
    {
        $this->promptTitle = $promptTitle;

        return $this;
    }

    
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    
    public function setPrompt(string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->formula1
            . $this->formula2
            . $this->type
            . $this->errorStyle
            . $this->operator
            . ($this->allowBlank ? 't' : 'f')
            . ($this->showDropDown ? 't' : 'f')
            . ($this->showInputMessage ? 't' : 'f')
            . ($this->showErrorMessage ? 't' : 'f')
            . $this->errorTitle
            . $this->error
            . $this->promptTitle
            . $this->prompt
            . $this->sqref
            . __CLASS__
        );
    }

    private ?string $sqref = null;

    public function getSqref(): ?string
    {
        return $this->sqref;
    }

    public function setSqref(?string $str): self
    {
        $this->sqref = $str;

        return $this;
    }
}
