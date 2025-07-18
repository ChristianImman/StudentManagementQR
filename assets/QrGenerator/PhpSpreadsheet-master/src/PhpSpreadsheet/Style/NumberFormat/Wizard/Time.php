<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

class Time extends DateTimeWizard
{
    
    public const HOURS_SHORT = 'h';

    
    public const HOURS_LONG = 'hh';

    
    public const MINUTES_SHORT = 'm';

    
    public const MINUTES_LONG = 'mm';

    
    public const SECONDS_SHORT = 's';

    
    public const SECONDS_LONG = 'ss';

    public const MORNING_AFTERNOON = 'AM/PM';

    protected const TIME_BLOCKS = [
        self::HOURS_LONG,
        self::HOURS_SHORT,
        self::MINUTES_LONG,
        self::MINUTES_SHORT,
        self::SECONDS_LONG,
        self::SECONDS_SHORT,
        self::MORNING_AFTERNOON,
    ];

    public const SEPARATOR_COLON = ':';
    public const SEPARATOR_SPACE_NONBREAKING = "\u{a0}";
    public const SEPARATOR_SPACE = ' ';

    protected const TIME_DEFAULT = [
        self::HOURS_LONG,
        self::MINUTES_LONG,
        self::SECONDS_LONG,
    ];

    
    protected array $separators;

    
    protected array $formatBlocks;

    
    public function __construct($separators = self::SEPARATOR_COLON, string ...$formatBlocks)
    {
        $separators ??= self::SEPARATOR_COLON;
        $formatBlocks = (count($formatBlocks) === 0) ? self::TIME_DEFAULT : $formatBlocks;

        $this->separators = $this->padSeparatorArray(
            is_array($separators) ? $separators : [$separators],
            count($formatBlocks) - 1
        );
        $this->formatBlocks = array_map([$this, 'mapFormatBlocks'], $formatBlocks);
    }

    private function mapFormatBlocks(string $value): string
    {
        
        
        if (in_array(mb_strtolower($value), self::TIME_BLOCKS, true)) {
            return mb_strtolower($value);
        } elseif (mb_strtoupper($value) === self::MORNING_AFTERNOON) {
            return mb_strtoupper($value);
        }

        
        return $this->wrapLiteral($value);
    }

    public function format(): string
    {
        return implode('', array_map([$this, 'intersperse'], $this->formatBlocks, $this->separators));
    }
}
