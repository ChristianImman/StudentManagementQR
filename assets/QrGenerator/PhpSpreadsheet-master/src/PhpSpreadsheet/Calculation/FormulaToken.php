<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;


class FormulaToken
{
    
    const TOKEN_TYPE_NOOP = 'Noop';
    const TOKEN_TYPE_OPERAND = 'Operand';
    const TOKEN_TYPE_FUNCTION = 'Function';
    const TOKEN_TYPE_SUBEXPRESSION = 'Subexpression';
    const TOKEN_TYPE_ARGUMENT = 'Argument';
    const TOKEN_TYPE_OPERATORPREFIX = 'OperatorPrefix';
    const TOKEN_TYPE_OPERATORINFIX = 'OperatorInfix';
    const TOKEN_TYPE_OPERATORPOSTFIX = 'OperatorPostfix';
    const TOKEN_TYPE_WHITESPACE = 'Whitespace';
    const TOKEN_TYPE_UNKNOWN = 'Unknown';

    
    const TOKEN_SUBTYPE_NOTHING = 'Nothing';
    const TOKEN_SUBTYPE_START = 'Start';
    const TOKEN_SUBTYPE_STOP = 'Stop';
    const TOKEN_SUBTYPE_TEXT = 'Text';
    const TOKEN_SUBTYPE_NUMBER = 'Number';
    const TOKEN_SUBTYPE_LOGICAL = 'Logical';
    const TOKEN_SUBTYPE_ERROR = 'Error';
    const TOKEN_SUBTYPE_RANGE = 'Range';
    const TOKEN_SUBTYPE_MATH = 'Math';
    const TOKEN_SUBTYPE_CONCATENATION = 'Concatenation';
    const TOKEN_SUBTYPE_INTERSECTION = 'Intersection';
    const TOKEN_SUBTYPE_UNION = 'Union';

    
    private string $value;

    
    private string $tokenType;

    
    private string $tokenSubType;

    
    public function __construct(string $value, string $tokenType = self::TOKEN_TYPE_UNKNOWN, string $tokenSubType = self::TOKEN_SUBTYPE_NOTHING)
    {
        
        $this->value = $value;
        $this->tokenType = $tokenType;
        $this->tokenSubType = $tokenSubType;
    }

    
    public function getValue(): string
    {
        return $this->value;
    }

    
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    
    public function setTokenType(string $value): void
    {
        $this->tokenType = $value;
    }

    
    public function getTokenSubType(): string
    {
        return $this->tokenSubType;
    }

    
    public function setTokenSubType(string $value): void
    {
        $this->tokenSubType = $value;
    }
}
