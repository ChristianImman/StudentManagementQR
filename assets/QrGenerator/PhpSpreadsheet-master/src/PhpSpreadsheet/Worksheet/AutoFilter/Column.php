<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter;

class Column
{
    const AUTOFILTER_FILTERTYPE_FILTER = 'filters';
    const AUTOFILTER_FILTERTYPE_CUSTOMFILTER = 'customFilters';
    
    
    const AUTOFILTER_FILTERTYPE_DYNAMICFILTER = 'dynamicFilter';
    
    
    const AUTOFILTER_FILTERTYPE_TOPTENFILTER = 'top10';

    
    private static array $filterTypes = [
        
        
        
        
        self::AUTOFILTER_FILTERTYPE_FILTER,
        self::AUTOFILTER_FILTERTYPE_CUSTOMFILTER,
        self::AUTOFILTER_FILTERTYPE_DYNAMICFILTER,
        self::AUTOFILTER_FILTERTYPE_TOPTENFILTER,
    ];

    
    const AUTOFILTER_COLUMN_JOIN_AND = 'and';
    const AUTOFILTER_COLUMN_JOIN_OR = 'or';

    
    private static array $ruleJoins = [
        self::AUTOFILTER_COLUMN_JOIN_AND,
        self::AUTOFILTER_COLUMN_JOIN_OR,
    ];

    
    private ?AutoFilter $parent;

    
    private string $columnIndex;

    
    private string $filterType = self::AUTOFILTER_FILTERTYPE_FILTER;

    
    private string $join = self::AUTOFILTER_COLUMN_JOIN_OR;

    
    private array $ruleset = [];

    
    private array $attributes = [];

    
    public function __construct(string $column, ?AutoFilter $parent = null)
    {
        $this->columnIndex = $column;
        $this->parent = $parent;
    }

    public function setEvaluatedFalse(): void
    {
        if ($this->parent !== null) {
            $this->parent->setEvaluated(false);
        }
    }

    
    public function getColumnIndex(): string
    {
        return $this->columnIndex;
    }

    
    public function setColumnIndex(string $column): static
    {
        $this->setEvaluatedFalse();
        
        $column = strtoupper($column);
        if ($this->parent !== null) {
            $this->parent->testColumnInRange($column);
        }

        $this->columnIndex = $column;

        return $this;
    }

    
    public function getParent(): ?AutoFilter
    {
        return $this->parent;
    }

    
    public function setParent(?AutoFilter $parent = null): static
    {
        $this->setEvaluatedFalse();
        $this->parent = $parent;

        return $this;
    }

    
    public function getFilterType(): string
    {
        return $this->filterType;
    }

    
    public function setFilterType(string $filterType): static
    {
        $this->setEvaluatedFalse();
        if (!in_array($filterType, self::$filterTypes)) {
            throw new PhpSpreadsheetException('Invalid filter type for column AutoFilter.');
        }
        if ($filterType === self::AUTOFILTER_FILTERTYPE_CUSTOMFILTER && count($this->ruleset) > 2) {
            throw new PhpSpreadsheetException('No more than 2 rules are allowed in a Custom Filter');
        }

        $this->filterType = $filterType;

        return $this;
    }

    
    public function getJoin(): string
    {
        return $this->join;
    }

    
    public function setJoin(string $join): static
    {
        $this->setEvaluatedFalse();
        
        $join = strtolower($join);
        if (!in_array($join, self::$ruleJoins)) {
            throw new PhpSpreadsheetException('Invalid rule connection for column AutoFilter.');
        }

        $this->join = $join;

        return $this;
    }

    
    public function setAttributes(array $attributes): static
    {
        $this->setEvaluatedFalse();
        $this->attributes = $attributes;

        return $this;
    }

    
    public function setAttribute(string $name, $value): static
    {
        $this->setEvaluatedFalse();
        $this->attributes[$name] = $value;

        return $this;
    }

    
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    
    public function getAttribute(string $name): null|float|int|string
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    public function ruleCount(): int
    {
        return count($this->ruleset);
    }

    
    public function getRules(): array
    {
        return $this->ruleset;
    }

    
    public function getRule(int $index): Column\Rule
    {
        if (!isset($this->ruleset[$index])) {
            $this->ruleset[$index] = new Column\Rule($this);
        }

        return $this->ruleset[$index];
    }

    
    public function createRule(): Column\Rule
    {
        $this->setEvaluatedFalse();
        if ($this->filterType === self::AUTOFILTER_FILTERTYPE_CUSTOMFILTER && count($this->ruleset) >= 2) {
            throw new PhpSpreadsheetException('No more than 2 rules are allowed in a Custom Filter');
        }
        $this->ruleset[] = new Column\Rule($this);

        return end($this->ruleset);
    }

    
    public function addRule(Column\Rule $rule): static
    {
        $this->setEvaluatedFalse();
        $rule->setParent($this);
        $this->ruleset[] = $rule;

        return $this;
    }

    
    public function deleteRule(int $index): static
    {
        $this->setEvaluatedFalse();
        if (isset($this->ruleset[$index])) {
            unset($this->ruleset[$index]);
            
            if (count($this->ruleset) <= 1) {
                $this->setJoin(self::AUTOFILTER_COLUMN_JOIN_OR);
            }
        }

        return $this;
    }

    
    public function clearRules(): static
    {
        $this->setEvaluatedFalse();
        $this->ruleset = [];
        $this->setJoin(self::AUTOFILTER_COLUMN_JOIN_OR);

        return $this;
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        
        foreach ($vars as $key => $value) {
            if ($key === 'parent') {
                
                $this->parent = null;
            } elseif ($key === 'ruleset') {
                
                $this->ruleset = [];
                foreach ($value as $k => $v) {
                    $cloned = clone $v;
                    $cloned->setParent($this); 
                    $this->ruleset[$k] = $cloned;
                }
            } else {
                $this->$key = $value;
            }
        }
    }
}
