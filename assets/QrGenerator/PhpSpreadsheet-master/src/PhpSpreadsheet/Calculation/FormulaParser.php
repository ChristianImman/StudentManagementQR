<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;


class FormulaParser
{
    
    const QUOTE_DOUBLE = '"';
    const QUOTE_SINGLE = '\'';
    const BRACKET_CLOSE = ']';
    const BRACKET_OPEN = '[';
    const BRACE_OPEN = '{';
    const BRACE_CLOSE = '}';
    const PAREN_OPEN = '(';
    const PAREN_CLOSE = ')';
    const SEMICOLON = ';';
    const WHITESPACE = ' ';
    const COMMA = ',';
    const ERROR_START = '

    const OPERATORS_SN = '+-';
    const OPERATORS_INFIX = '+-*/^&=><';
    const OPERATORS_POSTFIX = '%';

    
    private string $formula;

    
    private array $tokens = [];

    
    public function __construct(?string $formula = '')
    {
        
        if ($formula === null) {
            throw new Exception('Invalid parameter passed: formula');
        }

        
        $this->formula = trim($formula);
        
        $this->parseToTokens();
    }

    
    public function getFormula(): string
    {
        return $this->formula;
    }

    
    public function getToken(int $id = 0): FormulaToken
    {
        if (isset($this->tokens[$id])) {
            return $this->tokens[$id];
        }

        throw new Exception("Token with id $id does not exist.");
    }

    
    public function getTokenCount(): int
    {
        return count($this->tokens);
    }

    
    public function getTokens(): array
    {
        return $this->tokens;
    }

    
    private function parseToTokens(): void
    {
        
        

        
        $formulaLength = strlen($this->formula);
        if ($formulaLength < 2 || $this->formula[0] != '=') {
            return;
        }

        
        $tokens1 = $tokens2 = $stack = [];
        $inString = $inPath = $inRange = $inError = false;
        $nextToken = null;
        

        $index = 1;
        $value = '';

        $ERRORS = ['
        $COMPARATORS_MULTI = ['>=', '<=', '<>'];

        while ($index < $formulaLength) {
            

            
            
            
            if ($inString) {
                if ($this->formula[$index] == self::QUOTE_DOUBLE) {
                    if ((($index + 2) <= $formulaLength) && ($this->formula[$index + 1] == self::QUOTE_DOUBLE)) {
                        $value .= self::QUOTE_DOUBLE;
                        ++$index;
                    } else {
                        $inString = false;
                        $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND, FormulaToken::TOKEN_SUBTYPE_TEXT);
                        $value = '';
                    }
                } else {
                    $value .= $this->formula[$index];
                }
                ++$index;

                continue;
            }

            
            
            
            if ($inPath) {
                if ($this->formula[$index] == self::QUOTE_SINGLE) {
                    if ((($index + 2) <= $formulaLength) && ($this->formula[$index + 1] == self::QUOTE_SINGLE)) {
                        $value .= self::QUOTE_SINGLE;
                        ++$index;
                    } else {
                        $inPath = false;
                    }
                } else {
                    $value .= $this->formula[$index];
                }
                ++$index;

                continue;
            }

            
            
            
            if ($inRange) {
                if ($this->formula[$index] == self::BRACKET_CLOSE) {
                    $inRange = false;
                }
                $value .= $this->formula[$index];
                ++$index;

                continue;
            }

            
            
            if ($inError) {
                $value .= $this->formula[$index];
                ++$index;
                if (in_array($value, $ERRORS)) {
                    $inError = false;
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND, FormulaToken::TOKEN_SUBTYPE_ERROR);
                    $value = '';
                }

                continue;
            }

            
            if (str_contains(self::OPERATORS_SN, $this->formula[$index])) {
                if (strlen($value) > 1) {
                    if (preg_match('/^[1-9]{1}(\.\d+)?E{1}$/', $this->formula[$index]) != 0) {
                        $value .= $this->formula[$index];
                        ++$index;

                        continue;
                    }
                }
            }

            

            
            if ($this->formula[$index] == self::QUOTE_DOUBLE) {
                if ($value !== '') {
                    
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_UNKNOWN);
                    $value = '';
                }
                $inString = true;
                ++$index;

                continue;
            }

            if ($this->formula[$index] == self::QUOTE_SINGLE) {
                if ($value !== '') {
                    
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_UNKNOWN);
                    $value = '';
                }
                $inPath = true;
                ++$index;

                continue;
            }

            if ($this->formula[$index] == self::BRACKET_OPEN) {
                $inRange = true;
                $value .= self::BRACKET_OPEN;
                ++$index;

                continue;
            }

            if ($this->formula[$index] == self::ERROR_START) {
                if ($value !== '') {
                    
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_UNKNOWN);
                    $value = '';
                }
                $inError = true;
                $value .= self::ERROR_START;
                ++$index;

                continue;
            }

            
            if ($this->formula[$index] == self::BRACE_OPEN) {
                if ($value !== '') {
                    
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_UNKNOWN);
                    $value = '';
                }

                $tmp = new FormulaToken('ARRAY', FormulaToken::TOKEN_TYPE_FUNCTION, FormulaToken::TOKEN_SUBTYPE_START);
                $tokens1[] = $tmp;
                $stack[] = clone $tmp;

                $tmp = new FormulaToken('ARRAYROW', FormulaToken::TOKEN_TYPE_FUNCTION, FormulaToken::TOKEN_SUBTYPE_START);
                $tokens1[] = $tmp;
                $stack[] = clone $tmp;

                ++$index;

                continue;
            }

            if ($this->formula[$index] == self::SEMICOLON) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }

                
                $tmp = array_pop($stack);
                $tmp->setValue('');
                $tmp->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_STOP);
                $tokens1[] = $tmp;

                $tmp = new FormulaToken(',', FormulaToken::TOKEN_TYPE_ARGUMENT);
                $tokens1[] = $tmp;

                $tmp = new FormulaToken('ARRAYROW', FormulaToken::TOKEN_TYPE_FUNCTION, FormulaToken::TOKEN_SUBTYPE_START);
                $tokens1[] = $tmp;
                $stack[] = clone $tmp;

                ++$index;

                continue;
            }

            if ($this->formula[$index] == self::BRACE_CLOSE) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }

                
                $tmp = array_pop($stack);
                $tmp->setValue('');
                $tmp->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_STOP);
                $tokens1[] = $tmp;

                
                $tmp = array_pop($stack);
                $tmp->setValue('');
                $tmp->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_STOP);
                $tokens1[] = $tmp;

                ++$index;

                continue;
            }

            
            if ($this->formula[$index] == self::WHITESPACE) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }
                $tokens1[] = new FormulaToken('', FormulaToken::TOKEN_TYPE_WHITESPACE);
                ++$index;
                while (($this->formula[$index] == self::WHITESPACE) && ($index < $formulaLength)) {
                    ++$index;
                }

                continue;
            }

            
            if (($index + 2) <= $formulaLength) {
                if (in_array(substr($this->formula, $index, 2), $COMPARATORS_MULTI)) {
                    if ($value !== '') {
                        $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                        $value = '';
                    }
                    $tokens1[] = new FormulaToken(substr($this->formula, $index, 2), FormulaToken::TOKEN_TYPE_OPERATORINFIX, FormulaToken::TOKEN_SUBTYPE_LOGICAL);
                    $index += 2;

                    continue;
                }
            }

            
            if (str_contains(self::OPERATORS_INFIX, $this->formula[$index])) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }
                $tokens1[] = new FormulaToken($this->formula[$index], FormulaToken::TOKEN_TYPE_OPERATORINFIX);
                ++$index;

                continue;
            }

            
            if (str_contains(self::OPERATORS_POSTFIX, $this->formula[$index])) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }
                $tokens1[] = new FormulaToken($this->formula[$index], FormulaToken::TOKEN_TYPE_OPERATORPOSTFIX);
                ++$index;

                continue;
            }

            
            if ($this->formula[$index] == self::PAREN_OPEN) {
                if ($value !== '') {
                    $tmp = new FormulaToken($value, FormulaToken::TOKEN_TYPE_FUNCTION, FormulaToken::TOKEN_SUBTYPE_START);
                    $tokens1[] = $tmp;
                    $stack[] = clone $tmp;
                    $value = '';
                } else {
                    $tmp = new FormulaToken('', FormulaToken::TOKEN_TYPE_SUBEXPRESSION, FormulaToken::TOKEN_SUBTYPE_START);
                    $tokens1[] = $tmp;
                    $stack[] = clone $tmp;
                }
                ++$index;

                continue;
            }

            
            if ($this->formula[$index] == self::COMMA) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }

                
                $tmp = array_pop($stack);
                $tmp->setValue('');
                $tmp->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_STOP);
                $stack[] = $tmp;

                if ($tmp->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION) {
                    $tokens1[] = new FormulaToken(',', FormulaToken::TOKEN_TYPE_OPERATORINFIX, FormulaToken::TOKEN_SUBTYPE_UNION);
                } else {
                    $tokens1[] = new FormulaToken(',', FormulaToken::TOKEN_TYPE_ARGUMENT);
                }
                ++$index;

                continue;
            }

            
            if ($this->formula[$index] == self::PAREN_CLOSE) {
                if ($value !== '') {
                    $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
                    $value = '';
                }

                
                $tmp = array_pop($stack);
                $tmp->setValue('');
                $tmp->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_STOP);
                $tokens1[] = $tmp;

                ++$index;

                continue;
            }

            
            $value .= $this->formula[$index];
            ++$index;
        }

        
        if ($value !== '') {
            $tokens1[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERAND);
        }

        
        $tokenCount = count($tokens1);
        for ($i = 0; $i < $tokenCount; ++$i) {
            $token = $tokens1[$i];
            if (isset($tokens1[$i - 1])) {
                $previousToken = $tokens1[$i - 1];
            } else {
                $previousToken = null;
            }
            if (isset($tokens1[$i + 1])) {
                $nextToken = $tokens1[$i + 1];
            } else {
                $nextToken = null;
            }

            if ($token->getTokenType() != FormulaToken::TOKEN_TYPE_WHITESPACE) {
                $tokens2[] = $token;

                continue;
            }

            if ($previousToken === null) {
                continue;
            }

            if (
                !(
                    (($previousToken->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION) && ($previousToken->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                || (($previousToken->getTokenType() == FormulaToken::TOKEN_TYPE_SUBEXPRESSION) && ($previousToken->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                || ($previousToken->getTokenType() == FormulaToken::TOKEN_TYPE_OPERAND)
                )
            ) {
                continue;
            }

            if ($nextToken === null) {
                continue;
            }

            if (
                !(
                    (($nextToken->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION) && ($nextToken->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_START))
                || (($nextToken->getTokenType() == FormulaToken::TOKEN_TYPE_SUBEXPRESSION) && ($nextToken->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_START))
                || ($nextToken->getTokenType() == FormulaToken::TOKEN_TYPE_OPERAND)
                )
            ) {
                continue;
            }

            $tokens2[] = new FormulaToken($value, FormulaToken::TOKEN_TYPE_OPERATORINFIX, FormulaToken::TOKEN_SUBTYPE_INTERSECTION);
        }

        
        
        $this->tokens = [];

        $tokenCount = count($tokens2);
        for ($i = 0; $i < $tokenCount; ++$i) {
            $token = $tokens2[$i];
            if (isset($tokens2[$i - 1])) {
                $previousToken = $tokens2[$i - 1];
            } else {
                $previousToken = null;
            }

            if ($token->getTokenType() == FormulaToken::TOKEN_TYPE_OPERATORINFIX && $token->getValue() == '-') {
                if ($i == 0) {
                    $token->setTokenType(FormulaToken::TOKEN_TYPE_OPERATORPREFIX);
                } elseif (
                    (($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION)
                        && ($previousToken?->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                    || (($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_SUBEXPRESSION)
                        && ($previousToken?->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                    || ($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_OPERATORPOSTFIX)
                    || ($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_OPERAND)
                ) {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_MATH);
                } else {
                    $token->setTokenType(FormulaToken::TOKEN_TYPE_OPERATORPREFIX);
                }

                $this->tokens[] = $token;

                continue;
            }

            if ($token->getTokenType() == FormulaToken::TOKEN_TYPE_OPERATORINFIX && $token->getValue() == '+') {
                if ($i == 0) {
                    continue;
                } elseif (
                    (($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION)
                        && ($previousToken?->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                    || (($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_SUBEXPRESSION)
                        && ($previousToken?->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_STOP))
                    || ($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_OPERATORPOSTFIX)
                    || ($previousToken?->getTokenType() == FormulaToken::TOKEN_TYPE_OPERAND)
                ) {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_MATH);
                } else {
                    continue;
                }

                $this->tokens[] = $token;

                continue;
            }

            if (
                $token->getTokenType() == FormulaToken::TOKEN_TYPE_OPERATORINFIX
                && $token->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_NOTHING
            ) {
                if (str_contains('<>=', substr($token->getValue(), 0, 1))) {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_LOGICAL);
                } elseif ($token->getValue() == '&') {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_CONCATENATION);
                } else {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_MATH);
                }

                $this->tokens[] = $token;

                continue;
            }

            if (
                $token->getTokenType() == FormulaToken::TOKEN_TYPE_OPERAND
                && $token->getTokenSubType() == FormulaToken::TOKEN_SUBTYPE_NOTHING
            ) {
                if (!is_numeric($token->getValue())) {
                    if (strtoupper($token->getValue()) == 'TRUE' || strtoupper($token->getValue()) == 'FALSE') {
                        $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_LOGICAL);
                    } else {
                        $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_RANGE);
                    }
                } else {
                    $token->setTokenSubType(FormulaToken::TOKEN_SUBTYPE_NUMBER);
                }

                $this->tokens[] = $token;

                continue;
            }

            if ($token->getTokenType() == FormulaToken::TOKEN_TYPE_FUNCTION) {
                if ($token->getValue() !== '') {
                    if (str_starts_with($token->getValue(), '@')) {
                        $token->setValue(substr($token->getValue(), 1));
                    }
                }
            }

            $this->tokens[] = $token;
        }
    }
}
