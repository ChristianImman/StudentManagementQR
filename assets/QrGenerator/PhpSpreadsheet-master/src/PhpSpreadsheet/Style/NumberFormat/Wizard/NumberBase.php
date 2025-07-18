<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Stringable;

abstract class NumberBase implements Stringable
{
    protected const MAX_DECIMALS = 30;

    protected int $decimals = 2;

    protected ?string $locale = null;

    protected ?string $fullLocale = null;

    protected ?string $localeFormat = null;

    public function setDecimals(int $decimals = 2): void
    {
        $this->decimals = ($decimals > self::MAX_DECIMALS) ? self::MAX_DECIMALS : max($decimals, 0);
    }

    
    public function setLocale(?string $locale = null): void
    {
        if ($locale === null) {
            $this->localeFormat = $this->locale = $this->fullLocale = null;

            return;
        }

        $this->locale = $this->validateLocale($locale);

        if (class_exists(NumberFormatter::class)) {
            $this->localeFormat = $this->getLocaleFormat();
        }
    }

    
    abstract protected function getLocaleFormat(): string;

    
    private function validateLocale(string $locale): string
    {
        if (preg_match(Locale::STRUCTURE, $locale, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            throw new Exception("Invalid locale code '{$locale}'");
        }

        ['language' => $language, 'script' => $script, 'country' => $country] = $matches;
        
        $language = strtolower($language);
        $script = ($script === null) ? null : ucfirst(strtolower($script));
        $country = ($country === null) ? null : strtoupper($country);

        $this->fullLocale = implode('-', array_filter([$language, $script, $country]));

        return $country === null ? $language : "{$language}-{$country}";
    }

    public function format(): string
    {
        return NumberFormat::FORMAT_GENERAL;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
