<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Exception;

class Percentage extends NumberBase implements Wizard
{
    
    public function __construct(int $decimals = 2, ?string $locale = null)
    {
        $this->setDecimals($decimals);
        $this->setLocale($locale);
    }

    protected function getLocaleFormat(): string
    {
        $formatter = new Locale($this->fullLocale, NumberFormatter::PERCENT);

        return $this->decimals > 0
            ? str_replace('0', '0.' . str_repeat('0', $this->decimals), $formatter->format())
            : $formatter->format();
    }

    public function format(): string
    {
        if ($this->localeFormat !== null) {
            return $this->localeFormat;
        }

        return sprintf('0%s%%', $this->decimals > 0 ? '.' . str_repeat('0', $this->decimals) : null);
    }
}
