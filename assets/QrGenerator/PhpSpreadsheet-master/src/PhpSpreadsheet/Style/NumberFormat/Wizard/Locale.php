<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

use NumberFormatter;
use PhpOffice\PhpSpreadsheet\Exception;

final class Locale
{
    
    public const STRUCTURE = '/^(?P<language>[a-z]{2})([-_](?P<script>[a-z]{4}))?([-_](?P<country>[a-z]{2}))?$/i';

    private NumberFormatter $formatter;

    public function __construct(?string $locale, int $style)
    {
        if (class_exists(NumberFormatter::class) === false) {
            throw new Exception();
        }

        $formatterLocale = str_replace('-', '_', $locale ?? '');
        $this->formatter = new NumberFormatter($formatterLocale, $style);
        if ($this->formatter->getLocale() !== $formatterLocale) {
            throw new Exception("Unable to read locale data for '{$locale}'");
        }
    }

    public function format(bool $stripRlm = true): string
    {
        $str = $this->formatter->getPattern();

        return ($stripRlm && str_starts_with($str, "\xe2\x80\x8f")) ? substr($str, 3) : $str;
    }
}
