<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use PhpOffice\PhpSpreadsheet\Exception;

class Number extends NumberBase implements Wizard
{
    public const WITH_THOUSANDS_SEPARATOR = true;

    public const WITHOUT_THOUSANDS_SEPARATOR = false;

    protected bool $thousandsSeparator = true;

    
    public function __construct(
        int $decimals = 2,
        bool $thousandsSeparator = self::WITH_THOUSANDS_SEPARATOR,
        ?string $locale = null
    ) {
        $this->setDecimals($decimals);
        $this->setThousandsSeparator($thousandsSeparator);
        $this->setLocale($locale);
    }

    public function setThousandsSeparator(bool $thousandsSeparator = self::WITH_THOUSANDS_SEPARATOR): void
    {
        $this->thousandsSeparator = $thousandsSeparator;
    }

    
    protected function getLocaleFormat(): string
    {
        return $this->format();
    }

    public function format(): string
    {
        return sprintf(
            '%s0%s',
            $this->thousandsSeparator ? '
            $this->decimals > 0 ? '.' . str_repeat('0', $this->decimals) : null
        );
    }
}
