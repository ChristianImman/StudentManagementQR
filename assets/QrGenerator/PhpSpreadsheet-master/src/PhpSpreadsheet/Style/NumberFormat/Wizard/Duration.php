<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

class Duration extends DateTimeWizard
{
    public const DAYS_DURATION = 'd';

    
    public const HOURS_DURATION = '[h]';

    
    public const HOURS_SHORT = 'h';

    
    public const HOURS_LONG = 'hh';

    
    public const MINUTES_DURATION = '[m]';

    
    public const MINUTES_SHORT = 'm';

    
    public const MINUTES_LONG = 'mm';

    
    public const SECONDS_DURATION = '[s]';

    
    public const SECONDS_SHORT = 's';

    
    public const SECONDS_LONG = 'ss';

    protected const DURATION_BLOCKS = [
        self::DAYS_DURATION,
        self::HOURS_DURATION,
        self::HOURS_LONG,
        self::HOURS_SHORT,
        self::MINUTES_DURATION,
        self::MINUTES_LONG,
        self::MINUTES_SHORT,
        self::SECONDS_DURATION,
        self::SECONDS_LONG,
        self::SECONDS_SHORT,
    ];

    protected const DURATION_MASKS = [
        self::DAYS_DURATION => self::DAYS_DURATION,
        self::HOURS_DURATION => self::HOURS_SHORT,
        self::MINUTES_DURATION => self::MINUTES_LONG,
        self::SECONDS_DURATION => self::SECONDS_LONG,
    ];

    protected const DURATION_DEFAULTS = [
        self::HOURS_LONG => self::HOURS_DURATION,
        self::HOURS_SHORT => self::HOURS_DURATION,
        self::MINUTES_LONG => self::MINUTES_DURATION,
        self::MINUTES_SHORT => self::MINUTES_DURATION,
        self::SECONDS_LONG => self::SECONDS_DURATION,
        self::SECONDS_SHORT => self::SECONDS_DURATION,
    ];

    public const SEPARATOR_COLON = ':';
    public const SEPARATOR_SPACE_NONBREAKING = "\u{a0}";
    public const SEPARATOR_SPACE = ' ';

    public const DURATION_DEFAULT = [
        self::HOURS_DURATION,
        self::MINUTES_LONG,
        self::SECONDS_LONG,
    ];

    
    protected array $separators;

    
    protected array $formatBlocks;

    protected bool $durationIsSet = false;

    
    public function __construct($separators = self::SEPARATOR_COLON, string ...$formatBlocks)
    {
        $separators ??= self::SEPARATOR_COLON;
        $formatBlocks = (count($formatBlocks) === 0) ? self::DURATION_DEFAULT : $formatBlocks;

        $this->separators = $this->padSeparatorArray(
            is_array($separators) ? $separators : [$separators],
            count($formatBlocks) - 1
        );
        $this->formatBlocks = array_map([$this, 'mapFormatBlocks'], $formatBlocks);

        if ($this->durationIsSet === false) {
            
            
            $this->formatBlocks[0] = self::DURATION_DEFAULTS[mb_strtolower($this->formatBlocks[0])];
        }
    }

    private function mapFormatBlocks(string $value): string
    {
        
        if (in_array(mb_strtolower($value), self::DURATION_BLOCKS, true)) {
            if (array_key_exists(mb_strtolower($value), self::DURATION_MASKS)) {
                if ($this->durationIsSet) {
                    
                    
                    $value = self::DURATION_MASKS[mb_strtolower($value)];
                }
                $this->durationIsSet = true;
            }

            return mb_strtolower($value);
        }

        
        return $this->wrapLiteral($value);
    }

    public function format(): string
    {
        return implode('', array_map([$this, 'intersperse'], $this->formatBlocks, $this->separators));
    }
}
