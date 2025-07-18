<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

class Date extends DateTimeWizard
{
    
    public const YEAR_FULL = 'yyyy';

    
    public const YEAR_SHORT = 'yy';

    public const MONTH_FIRST_LETTER = 'mmmmm';
    
    public const MONTH_NAME_FULL = 'mmmm';
    
    public const MONTH_NAME_SHORT = 'mmm';
    
    public const MONTH_NUMBER_LONG = 'mm';

    
    public const MONTH_NUMBER_SHORT = 'm';

    
    public const WEEKDAY_NAME_LONG = 'dddd';

    
    public const WEEKDAY_NAME_SHORT = 'ddd';

    
    public const DAY_NUMBER_LONG = 'dd';

    
    public const DAY_NUMBER_SHORT = 'd';

    protected const DATE_BLOCKS = [
        self::YEAR_FULL,
        self::YEAR_SHORT,
        self::MONTH_FIRST_LETTER,
        self::MONTH_NAME_FULL,
        self::MONTH_NAME_SHORT,
        self::MONTH_NUMBER_LONG,
        self::MONTH_NUMBER_SHORT,
        self::WEEKDAY_NAME_LONG,
        self::WEEKDAY_NAME_SHORT,
        self::DAY_NUMBER_LONG,
        self::DAY_NUMBER_SHORT,
    ];

    public const SEPARATOR_DASH = '-';
    public const SEPARATOR_DOT = '.';
    public const SEPARATOR_SLASH = '/';
    public const SEPARATOR_SPACE_NONBREAKING = "\u{a0}";
    public const SEPARATOR_SPACE = ' ';

    protected const DATE_DEFAULT = [
        self::YEAR_FULL,
        self::MONTH_NUMBER_LONG,
        self::DAY_NUMBER_LONG,
    ];

    
    protected array $separators;

    
    protected array $formatBlocks;

    
    public function __construct($separators = self::SEPARATOR_DASH, string ...$formatBlocks)
    {
        $separators ??= self::SEPARATOR_DASH;
        $formatBlocks = (count($formatBlocks) === 0) ? self::DATE_DEFAULT : $formatBlocks;

        $this->separators = $this->padSeparatorArray(
            is_array($separators) ? $separators : [$separators],
            count($formatBlocks) - 1
        );
        $this->formatBlocks = array_map([$this, 'mapFormatBlocks'], $formatBlocks);
    }

    private function mapFormatBlocks(string $value): string
    {
        
        if (in_array(mb_strtolower($value), self::DATE_BLOCKS, true)) {
            return mb_strtolower($value);
        }

        
        return $this->wrapLiteral($value);
    }

    public function format(): string
    {
        return implode('', array_map([$this, 'intersperse'], $this->formatBlocks, $this->separators));
    }
}
