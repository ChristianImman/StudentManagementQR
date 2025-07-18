<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\IComparable;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalColorScale;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;

class Conditional implements IComparable
{
    
    const CONDITION_NONE = 'none';
    const CONDITION_BEGINSWITH = 'beginsWith';
    const CONDITION_CELLIS = 'cellIs';
    const CONDITION_COLORSCALE = 'colorScale';
    const CONDITION_CONTAINSBLANKS = 'containsBlanks';
    const CONDITION_CONTAINSERRORS = 'containsErrors';
    const CONDITION_CONTAINSTEXT = 'containsText';
    const CONDITION_DATABAR = 'dataBar';
    const CONDITION_ENDSWITH = 'endsWith';
    const CONDITION_EXPRESSION = 'expression';
    const CONDITION_NOTCONTAINSBLANKS = 'notContainsBlanks';
    const CONDITION_NOTCONTAINSERRORS = 'notContainsErrors';
    const CONDITION_NOTCONTAINSTEXT = 'notContainsText';
    const CONDITION_TIMEPERIOD = 'timePeriod';
    const CONDITION_DUPLICATES = 'duplicateValues';
    const CONDITION_UNIQUE = 'uniqueValues';

    private const CONDITION_TYPES = [
        self::CONDITION_BEGINSWITH,
        self::CONDITION_CELLIS,
        self::CONDITION_COLORSCALE,
        self::CONDITION_CONTAINSBLANKS,
        self::CONDITION_CONTAINSERRORS,
        self::CONDITION_CONTAINSTEXT,
        self::CONDITION_DATABAR,
        self::CONDITION_DUPLICATES,
        self::CONDITION_ENDSWITH,
        self::CONDITION_EXPRESSION,
        self::CONDITION_NONE,
        self::CONDITION_NOTCONTAINSBLANKS,
        self::CONDITION_NOTCONTAINSERRORS,
        self::CONDITION_NOTCONTAINSTEXT,
        self::CONDITION_TIMEPERIOD,
        self::CONDITION_UNIQUE,
    ];

    
    const OPERATOR_NONE = '';
    const OPERATOR_BEGINSWITH = 'beginsWith';
    const OPERATOR_ENDSWITH = 'endsWith';
    const OPERATOR_EQUAL = 'equal';
    const OPERATOR_GREATERTHAN = 'greaterThan';
    const OPERATOR_GREATERTHANOREQUAL = 'greaterThanOrEqual';
    const OPERATOR_LESSTHAN = 'lessThan';
    const OPERATOR_LESSTHANOREQUAL = 'lessThanOrEqual';
    const OPERATOR_NOTEQUAL = 'notEqual';
    const OPERATOR_CONTAINSTEXT = 'containsText';
    const OPERATOR_NOTCONTAINS = 'notContains';
    const OPERATOR_BETWEEN = 'between';
    const OPERATOR_NOTBETWEEN = 'notBetween';

    const TIMEPERIOD_TODAY = 'today';
    const TIMEPERIOD_YESTERDAY = 'yesterday';
    const TIMEPERIOD_TOMORROW = 'tomorrow';
    const TIMEPERIOD_LAST_7_DAYS = 'last7Days';
    const TIMEPERIOD_LAST_WEEK = 'lastWeek';
    const TIMEPERIOD_THIS_WEEK = 'thisWeek';
    const TIMEPERIOD_NEXT_WEEK = 'nextWeek';
    const TIMEPERIOD_LAST_MONTH = 'lastMonth';
    const TIMEPERIOD_THIS_MONTH = 'thisMonth';
    const TIMEPERIOD_NEXT_MONTH = 'nextMonth';

    
    private string $conditionType = self::CONDITION_NONE;

    
    private string $operatorType = self::OPERATOR_NONE;

    
    private string $text = '';

    
    private bool $stopIfTrue = false;

    
    private array $condition = [];

    private ?ConditionalDataBar $dataBar = null;

    private ?ConditionalColorScale $colorScale = null;

    private Style $style;

    private bool $noFormatSet = false;

    private int $priority = 0;

    
    public function __construct()
    {
        
        $this->style = new Style(false, true);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getNoFormatSet(): bool
    {
        return $this->noFormatSet;
    }

    public function setNoFormatSet(bool $noFormatSet): self
    {
        $this->noFormatSet = $noFormatSet;

        return $this;
    }

    
    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    
    public function setConditionType(string $type): static
    {
        $this->conditionType = $type;

        return $this;
    }

    
    public function getOperatorType(): string
    {
        return $this->operatorType;
    }

    
    public function setOperatorType(string $type): static
    {
        $this->operatorType = $type;

        return $this;
    }

    
    public function getText(): string
    {
        return $this->text;
    }

    
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    
    public function getStopIfTrue(): bool
    {
        return $this->stopIfTrue;
    }

    
    public function setStopIfTrue(bool $stopIfTrue): static
    {
        $this->stopIfTrue = $stopIfTrue;

        return $this;
    }

    
    public function getConditions(): array
    {
        return $this->condition;
    }

    
    public function setConditions($conditions): static
    {
        if (!is_array($conditions)) {
            $conditions = [$conditions];
        }
        $this->condition = $conditions;

        return $this;
    }

    
    public function addCondition($condition): static
    {
        $this->condition[] = $condition;

        return $this;
    }

    
    public function getStyle(mixed $cellData = null): Style
    {
        if ($this->conditionType === self::CONDITION_COLORSCALE && $cellData !== null && $this->colorScale !== null && is_numeric($cellData)) {
            $style = new Style();
            $style->getFill()->setFillType(Fill::FILL_SOLID);
            $style->getFill()->getStartColor()->setARGB($this->colorScale->getColorForValue((float) $cellData));

            return $style;
        }

        return $this->style;
    }

    
    public function setStyle(Style $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getDataBar(): ?ConditionalDataBar
    {
        return $this->dataBar;
    }

    public function setDataBar(ConditionalDataBar $dataBar): static
    {
        $this->dataBar = $dataBar;

        return $this;
    }

    public function getColorScale(): ?ConditionalColorScale
    {
        return $this->colorScale;
    }

    public function setColorScale(ConditionalColorScale $colorScale): static
    {
        $this->colorScale = $colorScale;

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->conditionType
            . $this->operatorType
            . implode(';', $this->condition)
            . $this->style->getHashCode()
            . __CLASS__
        );
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $this->$key = clone $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    
    public static function isValidConditionType(string $type): bool
    {
        return in_array($type, self::CONDITION_TYPES);
    }
}
