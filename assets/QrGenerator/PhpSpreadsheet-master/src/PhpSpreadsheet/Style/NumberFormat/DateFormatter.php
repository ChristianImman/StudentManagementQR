<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use PhpOffice\PhpSpreadsheet\Shared\Date;

class DateFormatter
{
    
    private const DATE_FORMAT_REPLACEMENTS = [
        
        '\\' => '',
        
        'am/pm' => 'A',
        
        'e' => 'Y',
        'yyyy' => 'Y',
        
        'yy' => 'y',
        
        'mmmmm' => 'M',
        
        'mmmm' => 'F',
        
        'mmm' => 'M',
        
        
        
        ':mm' => ':i',
        'mm:' => 'i:',
        
        'dddd' => 'l',
        
        'ddd' => 'D',
        
        'dd' => 'd',
        
        'd' => 'j',
        
        '.s' => '',
    ];

    
    private const DATE_FORMAT_REPLACEMENTS24 = [
        'hh' => 'H',
        'h' => 'G',
        
        'mm' => 'm',
        
        'm' => 'n',
        
        'ss' => 's',
    ];

    
    private const DATE_FORMAT_REPLACEMENTS12 = [
        'hh' => 'h',
        'h' => 'g',
        
        'mm' => 'm',
        
        'm' => 'n',
        
        'ss' => 's',
    ];

    private const HOURS_IN_DAY = 24;
    private const MINUTES_IN_DAY = 60 * self::HOURS_IN_DAY;
    private const SECONDS_IN_DAY = 60 * self::MINUTES_IN_DAY;
    private const INTERVAL_PRECISION = 10;
    private const INTERVAL_LEADING_ZERO = [
        '[hh]',
        '[mm]',
        '[ss]',
    ];
    private const INTERVAL_ROUND_PRECISION = [
        
        '[h]' => self::INTERVAL_PRECISION,
        '[hh]' => self::INTERVAL_PRECISION,
        '[m]' => self::INTERVAL_PRECISION,
        '[mm]' => self::INTERVAL_PRECISION,
        
        '[s]' => 0,
        '[ss]' => 0,
    ];
    private const INTERVAL_MULTIPLIER = [
        '[h]' => self::HOURS_IN_DAY,
        '[hh]' => self::HOURS_IN_DAY,
        '[m]' => self::MINUTES_IN_DAY,
        '[mm]' => self::MINUTES_IN_DAY,
        '[s]' => self::SECONDS_IN_DAY,
        '[ss]' => self::SECONDS_IN_DAY,
    ];

    
    private static function tryInterval(bool &$seekingBracket, string &$block, mixed $value, string $format): void
    {
        if ($seekingBracket) {
            if (str_contains($block, $format)) {
                $hours = (string) (int) round(
                    self::INTERVAL_MULTIPLIER[$format] * $value,
                    self::INTERVAL_ROUND_PRECISION[$format]
                );
                if (strlen($hours) === 1 && in_array($format, self::INTERVAL_LEADING_ZERO, true)) {
                    $hours = "0$hours";
                }
                $block = str_replace($format, $hours, $block);
                $seekingBracket = false;
            }
        }
    }

    
    public static function format(mixed $value, string $format): string
    {
        
        
        
        
        $format = (string) preg_replace('/^(\[DBNum\d\])*(\[\$[^\]]*\])/i', '', $format);

        
        
        
        $callable = [self::class, 'setLowercaseCallback'];
        $format = (string) preg_replace_callback('/(?:^|")([^"]*)(?:$|")/', $callable, $format);

        

        $blocks = explode('"', $format);
        foreach ($blocks as $key => &$block) {
            if ($key % 2 == 0) {
                $block = strtr($block, self::DATE_FORMAT_REPLACEMENTS);
                if (!strpos($block, 'A')) {
                    
                    
                    $seekingBracket = true;
                    self::tryInterval($seekingBracket, $block, $value, '[h]');
                    self::tryInterval($seekingBracket, $block, $value, '[hh]');
                    self::tryInterval($seekingBracket, $block, $value, '[mm]');
                    self::tryInterval($seekingBracket, $block, $value, '[m]');
                    self::tryInterval($seekingBracket, $block, $value, '[s]');
                    self::tryInterval($seekingBracket, $block, $value, '[ss]');
                    $block = strtr($block, self::DATE_FORMAT_REPLACEMENTS24);
                } else {
                    
                    $block = strtr($block, self::DATE_FORMAT_REPLACEMENTS12);
                }
            }
        }
        $format = implode('"', $blocks);

        
        
        $callback = [self::class, 'escapeQuotesCallback'];
        $format = (string) preg_replace_callback('/"(.*)"/U', $callback, $format);

        $dateObj = Date::excelToDateTimeObject($value);
        
        
        
        $format = (string) \preg_replace('/\\\:m/', ':i', $format);
        $microseconds = (int) $dateObj->format('u');
        if (str_contains($format, ':s.000')) {
            $milliseconds = (int) round($microseconds / 1000.0);
            if ($milliseconds === 1000) {
                $milliseconds = 0;
                $dateObj->modify('+1 second');
            }
            $dateObj->modify("-$microseconds microseconds");
            $format = str_replace(':s.000', ':s.' . sprintf('%03d', $milliseconds), $format);
        } elseif (str_contains($format, ':s.00')) {
            $centiseconds = (int) round($microseconds / 10000.0);
            if ($centiseconds === 100) {
                $centiseconds = 0;
                $dateObj->modify('+1 second');
            }
            $dateObj->modify("-$microseconds microseconds");
            $format = str_replace(':s.00', ':s.' . sprintf('%02d', $centiseconds), $format);
        } elseif (str_contains($format, ':s.0')) {
            $deciseconds = (int) round($microseconds / 100000.0);
            if ($deciseconds === 10) {
                $deciseconds = 0;
                $dateObj->modify('+1 second');
            }
            $dateObj->modify("-$microseconds microseconds");
            $format = str_replace(':s.0', ':s.' . sprintf('%1d', $deciseconds), $format);
        } else { 
            if ($microseconds >= 500000) {
                $dateObj->modify('+1 second');
            }
            $dateObj->modify("-$microseconds microseconds");
        }

        return $dateObj->format($format);
    }

    private static function setLowercaseCallback(array $matches): string
    {
        return mb_strtolower($matches[0]);
    }

    private static function escapeQuotesCallback(array $matches): string
    {
        return '\\' . implode('\\', mb_str_split($matches[1], 1, 'UTF-8'));
    }
}
