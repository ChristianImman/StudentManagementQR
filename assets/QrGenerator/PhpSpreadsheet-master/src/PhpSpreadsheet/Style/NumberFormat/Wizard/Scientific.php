<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use PhpOffice\PhpSpreadsheet\Exception;

class Scientific extends NumberBase implements Wizard
{
    
    public function __construct(int $decimals = 2, ?string $locale = null)
    {
        $this->setDecimals($decimals);
        $this->setLocale($locale);
    }

    protected function getLocaleFormat(): string
    {
        return $this->format();
    }

    public function format(): string
    {
        return sprintf('0%sE+00', $this->decimals > 0 ? '.' . str_repeat('0', $this->decimals) : null);
    }
}
