<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;

class Rule
{
    const AUTOFILTER_RULETYPE_FILTER = 'filter';
    const AUTOFILTER_RULETYPE_DATEGROUP = 'dateGroupItem';
    const AUTOFILTER_RULETYPE_CUSTOMFILTER = 'customFilter';
    const AUTOFILTER_RULETYPE_DYNAMICFILTER = 'dynamicFilter';
    const AUTOFILTER_RULETYPE_TOPTENFILTER = 'top10Filter';

    private const RULE_TYPES = [
        
        
        
        
        self::AUTOFILTER_RULETYPE_FILTER,
        self::AUTOFILTER_RULETYPE_DATEGROUP,
        self::AUTOFILTER_RULETYPE_CUSTOMFILTER,
        self::AUTOFILTER_RULETYPE_DYNAMICFILTER,
        self::AUTOFILTER_RULETYPE_TOPTENFILTER,
    ];

    const AUTOFILTER_RULETYPE_DATEGROUP_YEAR = 'year';
    const AUTOFILTER_RULETYPE_DATEGROUP_MONTH = 'month';
    const AUTOFILTER_RULETYPE_DATEGROUP_DAY = 'day';
    const AUTOFILTER_RULETYPE_DATEGROUP_HOUR = 'hour';
    const AUTOFILTER_RULETYPE_DATEGROUP_MINUTE = 'minute';
    const AUTOFILTER_RULETYPE_DATEGROUP_SECOND = 'second';

    private const DATE_TIME_GROUPS = [
        self::AUTOFILTER_RULETYPE_DATEGROUP_YEAR,
        self::AUTOFILTER_RULETYPE_DATEGROUP_MONTH,
        self::AUTOFILTER_RULETYPE_DATEGROUP_DAY,
        self::AUTOFILTER_RULETYPE_DATEGROUP_HOUR,
        self::AUTOFILTER_RULETYPE_DATEGROUP_MINUTE,
        self::AUTOFILTER_RULETYPE_DATEGROUP_SECOND,
    ];

    const AUTOFILTER_RULETYPE_DYNAMIC_YESTERDAY = 'yesterday';
    const AUTOFILTER_RULETYPE_DYNAMIC_TODAY = 'today';
    const AUTOFILTER_RULETYPE_DYNAMIC_TOMORROW = 'tomorrow';
    const AUTOFILTER_RULETYPE_DYNAMIC_YEARTODATE = 'yearToDate';
    const AUTOFILTER_RULETYPE_DYNAMIC_THISYEAR = 'thisYear';
    const AUTOFILTER_RULETYPE_DYNAMIC_THISQUARTER = 'thisQuarter';
    const AUTOFILTER_RULETYPE_DYNAMIC_THISMONTH = 'thisMonth';
    const AUTOFILTER_RULETYPE_DYNAMIC_THISWEEK = 'thisWeek';
    const AUTOFILTER_RULETYPE_DYNAMIC_LASTYEAR = 'lastYear';
    const AUTOFILTER_RULETYPE_DYNAMIC_LASTQUARTER = 'lastQuarter';
    const AUTOFILTER_RULETYPE_DYNAMIC_LASTMONTH = 'lastMonth';
    const AUTOFILTER_RULETYPE_DYNAMIC_LASTWEEK = 'lastWeek';
    const AUTOFILTER_RULETYPE_DYNAMIC_NEXTYEAR = 'nextYear';
    const AUTOFILTER_RULETYPE_DYNAMIC_NEXTQUARTER = 'nextQuarter';
    const AUTOFILTER_RULETYPE_DYNAMIC_NEXTMONTH = 'nextMonth';
    const AUTOFILTER_RULETYPE_DYNAMIC_NEXTWEEK = 'nextWeek';
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_1 = 'M1';
    const AUTOFILTER_RULETYPE_DYNAMIC_JANUARY = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_1;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_2 = 'M2';
    const AUTOFILTER_RULETYPE_DYNAMIC_FEBRUARY = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_2;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_3 = 'M3';
    const AUTOFILTER_RULETYPE_DYNAMIC_MARCH = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_3;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_4 = 'M4';
    const AUTOFILTER_RULETYPE_DYNAMIC_APRIL = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_4;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_5 = 'M5';
    const AUTOFILTER_RULETYPE_DYNAMIC_MAY = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_5;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_6 = 'M6';
    const AUTOFILTER_RULETYPE_DYNAMIC_JUNE = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_6;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_7 = 'M7';
    const AUTOFILTER_RULETYPE_DYNAMIC_JULY = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_7;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_8 = 'M8';
    const AUTOFILTER_RULETYPE_DYNAMIC_AUGUST = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_8;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_9 = 'M9';
    const AUTOFILTER_RULETYPE_DYNAMIC_SEPTEMBER = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_9;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_10 = 'M10';
    const AUTOFILTER_RULETYPE_DYNAMIC_OCTOBER = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_10;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_11 = 'M11';
    const AUTOFILTER_RULETYPE_DYNAMIC_NOVEMBER = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_11;
    const AUTOFILTER_RULETYPE_DYNAMIC_MONTH_12 = 'M12';
    const AUTOFILTER_RULETYPE_DYNAMIC_DECEMBER = self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_12;
    const AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_1 = 'Q1';
    const AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_2 = 'Q2';
    const AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_3 = 'Q3';
    const AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_4 = 'Q4';
    const AUTOFILTER_RULETYPE_DYNAMIC_ABOVEAVERAGE = 'aboveAverage';
    const AUTOFILTER_RULETYPE_DYNAMIC_BELOWAVERAGE = 'belowAverage';

    private const DYNAMIC_TYPES = [
        self::AUTOFILTER_RULETYPE_DYNAMIC_YESTERDAY,
        self::AUTOFILTER_RULETYPE_DYNAMIC_TODAY,
        self::AUTOFILTER_RULETYPE_DYNAMIC_TOMORROW,
        self::AUTOFILTER_RULETYPE_DYNAMIC_YEARTODATE,
        self::AUTOFILTER_RULETYPE_DYNAMIC_THISYEAR,
        self::AUTOFILTER_RULETYPE_DYNAMIC_THISQUARTER,
        self::AUTOFILTER_RULETYPE_DYNAMIC_THISMONTH,
        self::AUTOFILTER_RULETYPE_DYNAMIC_THISWEEK,
        self::AUTOFILTER_RULETYPE_DYNAMIC_LASTYEAR,
        self::AUTOFILTER_RULETYPE_DYNAMIC_LASTQUARTER,
        self::AUTOFILTER_RULETYPE_DYNAMIC_LASTMONTH,
        self::AUTOFILTER_RULETYPE_DYNAMIC_LASTWEEK,
        self::AUTOFILTER_RULETYPE_DYNAMIC_NEXTYEAR,
        self::AUTOFILTER_RULETYPE_DYNAMIC_NEXTQUARTER,
        self::AUTOFILTER_RULETYPE_DYNAMIC_NEXTMONTH,
        self::AUTOFILTER_RULETYPE_DYNAMIC_NEXTWEEK,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_1,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_2,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_3,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_4,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_5,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_6,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_7,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_8,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_9,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_10,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_11,
        self::AUTOFILTER_RULETYPE_DYNAMIC_MONTH_12,
        self::AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_1,
        self::AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_2,
        self::AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_3,
        self::AUTOFILTER_RULETYPE_DYNAMIC_QUARTER_4,
        self::AUTOFILTER_RULETYPE_DYNAMIC_ABOVEAVERAGE,
        self::AUTOFILTER_RULETYPE_DYNAMIC_BELOWAVERAGE,
    ];

    
    const AUTOFILTER_COLUMN_RULE_EQUAL = 'equal';
    const AUTOFILTER_COLUMN_RULE_NOTEQUAL = 'notEqual';
    const AUTOFILTER_COLUMN_RULE_GREATERTHAN = 'greaterThan';
    const AUTOFILTER_COLUMN_RULE_GREATERTHANOREQUAL = 'greaterThanOrEqual';
    const AUTOFILTER_COLUMN_RULE_LESSTHAN = 'lessThan';
    const AUTOFILTER_COLUMN_RULE_LESSTHANOREQUAL = 'lessThanOrEqual';

    private const OPERATORS = [
        self::AUTOFILTER_COLUMN_RULE_EQUAL,
        self::AUTOFILTER_COLUMN_RULE_NOTEQUAL,
        self::AUTOFILTER_COLUMN_RULE_GREATERTHAN,
        self::AUTOFILTER_COLUMN_RULE_GREATERTHANOREQUAL,
        self::AUTOFILTER_COLUMN_RULE_LESSTHAN,
        self::AUTOFILTER_COLUMN_RULE_LESSTHANOREQUAL,
    ];

    const AUTOFILTER_COLUMN_RULE_TOPTEN_BY_VALUE = 'byValue';
    const AUTOFILTER_COLUMN_RULE_TOPTEN_PERCENT = 'byPercent';

    private const TOP_TEN_VALUE = [
        self::AUTOFILTER_COLUMN_RULE_TOPTEN_BY_VALUE,
        self::AUTOFILTER_COLUMN_RULE_TOPTEN_PERCENT,
    ];

    const AUTOFILTER_COLUMN_RULE_TOPTEN_TOP = 'top';
    const AUTOFILTER_COLUMN_RULE_TOPTEN_BOTTOM = 'bottom';

    private const TOP_TEN_TYPE = [
        self::AUTOFILTER_COLUMN_RULE_TOPTEN_TOP,
        self::AUTOFILTER_COLUMN_RULE_TOPTEN_BOTTOM,
    ];

    
    
    
    
    
    
    
    
    
    
    
    
    private ?Column $parent;

    
    private string $ruleType = self::AUTOFILTER_RULETYPE_FILTER;

    
    private $value = '';

    
    private string $operator = self::AUTOFILTER_COLUMN_RULE_EQUAL;

    
    private string $grouping = '';

    
    public function __construct(?Column $parent = null)
    {
        $this->parent = $parent;
    }

    private function setEvaluatedFalse(): void
    {
        if ($this->parent !== null) {
            $this->parent->setEvaluatedFalse();
        }
    }

    
    public function getRuleType(): string
    {
        return $this->ruleType;
    }

    
    public function setRuleType(string $ruleType): static
    {
        $this->setEvaluatedFalse();
        if (!in_array($ruleType, self::RULE_TYPES)) {
            throw new PhpSpreadsheetException('Invalid rule type for column AutoFilter Rule.');
        }

        $this->ruleType = $ruleType;

        return $this;
    }

    
    public function getValue()
    {
        return $this->value;
    }

    
    public function setValue($value): static
    {
        $this->setEvaluatedFalse();
        if (is_array($value)) {
            $grouping = -1;
            foreach ($value as $key => $v) {
                
                if (!in_array($key, self::DATE_TIME_GROUPS)) {
                    
                    unset($value[$key]);
                } else {
                    
                    $grouping = max($grouping, array_search($key, self::DATE_TIME_GROUPS));
                }
            }
            if (count($value) == 0) {
                throw new PhpSpreadsheetException('Invalid rule value for column AutoFilter Rule.');
            }
            
            $this->setGrouping(self::DATE_TIME_GROUPS[$grouping]);
        }
        $this->value = $value;

        return $this;
    }

    
    public function getOperator(): string
    {
        return $this->operator;
    }

    
    public function setOperator(string $operator): static
    {
        $this->setEvaluatedFalse();
        if (empty($operator)) {
            $operator = self::AUTOFILTER_COLUMN_RULE_EQUAL;
        }
        if (
            (!in_array($operator, self::OPERATORS))
            && (!in_array($operator, self::TOP_TEN_VALUE))
        ) {
            throw new PhpSpreadsheetException('Invalid operator for column AutoFilter Rule.');
        }
        $this->operator = $operator;

        return $this;
    }

    
    public function getGrouping(): string
    {
        return $this->grouping;
    }

    
    public function setGrouping(string $grouping): static
    {
        $this->setEvaluatedFalse();
        if (
            (!in_array($grouping, self::DATE_TIME_GROUPS))
            && (!in_array($grouping, self::DYNAMIC_TYPES))
            && (!in_array($grouping, self::TOP_TEN_TYPE))
        ) {
            throw new PhpSpreadsheetException('Invalid grouping for column AutoFilter Rule.');
        }
        $this->grouping = $grouping;

        return $this;
    }

    
    public function setRule(string $operator, $value, ?string $grouping = null): static
    {
        $this->setEvaluatedFalse();
        $this->setOperator($operator);
        $this->setValue($value);
        
        
        
        if ($grouping !== null) {
            $this->setGrouping($grouping);
        }

        return $this;
    }

    
    public function getParent(): ?Column
    {
        return $this->parent;
    }

    
    public function setParent(?Column $parent = null): static
    {
        $this->setEvaluatedFalse();
        $this->parent = $parent;

        return $this;
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                if ($key == 'parent') { 
                    
                    $this->$key = null;
                }
            } else {
                $this->$key = $value;
            }
        }
    }
}
