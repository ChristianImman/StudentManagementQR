<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

class CalculationLocale extends CalculationBase
{
    public const FORMULA_OPEN_FUNCTION_BRACE = '(';
    public const FORMULA_CLOSE_FUNCTION_BRACE = ')';
    public const FORMULA_OPEN_MATRIX_BRACE = '{';
    public const FORMULA_CLOSE_MATRIX_BRACE = '}';
    public const FORMULA_STRING_QUOTE = '"';

    
    public const CALCULATION_REGEXP_STRIP_XLFN_XLWS = '/(_xlfn[.])?(_xlws[.])?(?=[\p{L}][\p{L}\p{N}\.]*[\s]*[(])/';

    
    protected static string $localeLanguage = 'en_us'; 

    
    protected static array $validLocaleLanguages = [
        'en', 
    ];

    
    protected static string $localeArgumentSeparator = ',';

    
    protected static array $localeFunctions = [];

    
    protected static array $localeBoolean = [
        'TRUE' => 'TRUE',
        'FALSE' => 'FALSE',
        'NULL' => 'NULL',
    ];

    
    protected static array $falseTrueArray = [];

    public static function getLocaleBoolean(string $index): string
    {
        return self::$localeBoolean[$index];
    }

    protected static function loadLocales(): void
    {
        $localeFileDirectory = __DIR__ . '/locale/';
        $localeFileNames = glob($localeFileDirectory . '*', GLOB_ONLYDIR) ?: [];
        foreach ($localeFileNames as $filename) {
            $filename = substr($filename, strlen($localeFileDirectory));
            if ($filename != 'en') {
                self::$validLocaleLanguages[] = $filename;
            }
        }
    }

    
    public static function getTRUE(): string
    {
        return self::$localeBoolean['TRUE'];
    }

    
    public static function getFALSE(): string
    {
        return self::$localeBoolean['FALSE'];
    }

    
    public function getLocale(): string
    {
        return self::$localeLanguage;
    }

    protected function getLocaleFile(string $localeDir, string $locale, string $language, string $file): string
    {
        $localeFileName = $localeDir . str_replace('_', DIRECTORY_SEPARATOR, $locale)
            . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($localeFileName)) {
            
            $localeFileName = $localeDir . $language . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($localeFileName)) {
                throw new Exception('Locale file not found');
            }
        }

        return $localeFileName;
    }

    
    public function getFalseTrueArray(): array
    {
        if (!empty(self::$falseTrueArray)) {
            return self::$falseTrueArray;
        }
        if (count(self::$validLocaleLanguages) == 1) {
            self::loadLocales();
        }
        $falseTrueArray = [['FALSE'], ['TRUE']];
        foreach (self::$validLocaleLanguages as $language) {
            if (str_starts_with($language, 'en')) {
                continue;
            }
            $locale = $language;
            if (str_contains($locale, '_')) {
                [$language] = explode('_', $locale);
            }
            $localeDir = implode(DIRECTORY_SEPARATOR, [__DIR__, 'locale', null]);

            try {
                $functionNamesFile = $this->getLocaleFile($localeDir, $locale, $language, 'functions');
            } catch (Exception $e) {
                continue;
            }
            
            $localeFunctions = file($functionNamesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($localeFunctions as $localeFunction) {
                [$localeFunction] = explode('
                if (str_contains($localeFunction, '=')) {
                    [$fName, $lfName] = array_map('trim', explode('=', $localeFunction));
                    if ($fName === 'FALSE') {
                        $falseTrueArray[0][] = $lfName;
                    } elseif ($fName === 'TRUE') {
                        $falseTrueArray[1][] = $lfName;
                    }
                }
            }
        }
        self::$falseTrueArray = $falseTrueArray;

        return $falseTrueArray;
    }

    
    public function setLocale(string $locale): bool
    {
        
        $language = $locale = strtolower($locale);
        if (str_contains($locale, '_')) {
            [$language] = explode('_', $locale);
        }
        if (count(self::$validLocaleLanguages) == 1) {
            self::loadLocales();
        }

        
        if (in_array($language, self::$validLocaleLanguages, true)) {
            
            self::$localeFunctions = [];
            self::$localeArgumentSeparator = ',';
            self::$localeBoolean = ['TRUE' => 'TRUE', 'FALSE' => 'FALSE', 'NULL' => 'NULL'];

            
            if ($locale !== 'en_us') {
                $localeDir = implode(DIRECTORY_SEPARATOR, [__DIR__, 'locale', null]);

                
                try {
                    $functionNamesFile = $this->getLocaleFile($localeDir, $locale, $language, 'functions');
                } catch (Exception $e) {
                    return false;
                }

                
                $localeFunctions = file($functionNamesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                $phpSpreadsheetFunctions = &self::getFunctionsAddress();
                foreach ($localeFunctions as $localeFunction) {
                    [$localeFunction] = explode('
                    if (str_contains($localeFunction, '=')) {
                        [$fName, $lfName] = array_map('trim', explode('=', $localeFunction));
                        if ((str_starts_with($fName, '*') || isset($phpSpreadsheetFunctions[$fName])) && ($lfName != '') && ($fName != $lfName)) {
                            self::$localeFunctions[$fName] = $lfName;
                        }
                    }
                }
                
                if (isset(self::$localeFunctions['TRUE'])) {
                    self::$localeBoolean['TRUE'] = self::$localeFunctions['TRUE'];
                }
                if (isset(self::$localeFunctions['FALSE'])) {
                    self::$localeBoolean['FALSE'] = self::$localeFunctions['FALSE'];
                }

                try {
                    $configFile = $this->getLocaleFile($localeDir, $locale, $language, 'config');
                } catch (Exception) {
                    return false;
                }

                $localeSettings = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($localeSettings as $localeSetting) {
                    [$localeSetting] = explode('
                    if (str_contains($localeSetting, '=')) {
                        [$settingName, $settingValue] = array_map('trim', explode('=', $localeSetting));
                        $settingName = strtoupper($settingName);
                        if ($settingValue !== '') {
                            switch ($settingName) {
                                case 'ARGUMENTSEPARATOR':
                                    self::$localeArgumentSeparator = $settingValue;

                                    break;
                            }
                        }
                    }
                }
            }

            self::$functionReplaceFromExcel = self::$functionReplaceToExcel
            = self::$functionReplaceFromLocale = self::$functionReplaceToLocale = null;
            self::$localeLanguage = $locale;

            return true;
        }

        return false;
    }

    public static function translateSeparator(
        string $fromSeparator,
        string $toSeparator,
        string $formula,
        int &$inBracesLevel,
        string $openBrace = self::FORMULA_OPEN_FUNCTION_BRACE,
        string $closeBrace = self::FORMULA_CLOSE_FUNCTION_BRACE
    ): string {
        $strlen = mb_strlen($formula);
        for ($i = 0; $i < $strlen; ++$i) {
            $chr = mb_substr($formula, $i, 1);
            switch ($chr) {
                case $openBrace:
                    ++$inBracesLevel;

                    break;
                case $closeBrace:
                    --$inBracesLevel;

                    break;
                case $fromSeparator:
                    if ($inBracesLevel > 0) {
                        $formula = mb_substr($formula, 0, $i) . $toSeparator . mb_substr($formula, $i + 1);
                    }
            }
        }

        return $formula;
    }

    
    protected static function translateFormulaBlock(
        array $from,
        array $to,
        string $formula,
        int &$inFunctionBracesLevel,
        int &$inMatrixBracesLevel,
        string $fromSeparator,
        string $toSeparator
    ): string {
        
        $formula = (string) preg_replace($from, $to, $formula);

        
        $formula = self::translateSeparator(';', '|', $formula, $inMatrixBracesLevel, self::FORMULA_OPEN_MATRIX_BRACE, self::FORMULA_CLOSE_MATRIX_BRACE);
        $formula = self::translateSeparator(',', '!', $formula, $inMatrixBracesLevel, self::FORMULA_OPEN_MATRIX_BRACE, self::FORMULA_CLOSE_MATRIX_BRACE);
        
        $formula = self::translateSeparator($fromSeparator, $toSeparator, $formula, $inFunctionBracesLevel);
        
        $formula = self::translateSeparator('|', ';', $formula, $inMatrixBracesLevel, self::FORMULA_OPEN_MATRIX_BRACE, self::FORMULA_CLOSE_MATRIX_BRACE);
        $formula = self::translateSeparator('!', ',', $formula, $inMatrixBracesLevel, self::FORMULA_OPEN_MATRIX_BRACE, self::FORMULA_CLOSE_MATRIX_BRACE);

        return $formula;
    }

    
    protected static function translateFormula(array $from, array $to, string $formula, string $fromSeparator, string $toSeparator): string
    {
        
        
        if (self::$localeLanguage !== 'en_us') {
            $inFunctionBracesLevel = 0;
            $inMatrixBracesLevel = 0;
            
            if (str_contains($formula, self::FORMULA_STRING_QUOTE)) {
                
                
                $temp = explode(self::FORMULA_STRING_QUOTE, $formula);
                $notWithinQuotes = false;
                foreach ($temp as &$value) {
                    
                    $notWithinQuotes = $notWithinQuotes === false;
                    if ($notWithinQuotes === true) {
                        $value = self::translateFormulaBlock($from, $to, $value, $inFunctionBracesLevel, $inMatrixBracesLevel, $fromSeparator, $toSeparator);
                    }
                }
                unset($value);
                
                $formula = implode(self::FORMULA_STRING_QUOTE, $temp);
            } else {
                
                $formula = self::translateFormulaBlock($from, $to, $formula, $inFunctionBracesLevel, $inMatrixBracesLevel, $fromSeparator, $toSeparator);
            }
        }

        return $formula;
    }

    
    private static ?array $functionReplaceFromExcel;

    
    private static ?array $functionReplaceToLocale;

    public function translateFormulaToLocale(string $formula): string
    {
        $formula = preg_replace(self::CALCULATION_REGEXP_STRIP_XLFN_XLWS, '', $formula) ?? '';
        
        if (self::$functionReplaceFromExcel === null) {
            self::$functionReplaceFromExcel = [];
            foreach (array_keys(self::$localeFunctions) as $excelFunctionName) {
                self::$functionReplaceFromExcel[] = '/(@?[^\w\.])' . preg_quote($excelFunctionName, '/') . '([\s]*\()/ui';
            }
            foreach (array_keys(self::$localeBoolean) as $excelBoolean) {
                self::$functionReplaceFromExcel[] = '/(@?[^\w\.])' . preg_quote($excelBoolean, '/') . '([^\w\.])/ui';
            }
        }

        if (self::$functionReplaceToLocale === null) {
            self::$functionReplaceToLocale = [];
            foreach (self::$localeFunctions as $localeFunctionName) {
                self::$functionReplaceToLocale[] = '$1' . trim($localeFunctionName) . '$2';
            }
            foreach (self::$localeBoolean as $localeBoolean) {
                self::$functionReplaceToLocale[] = '$1' . trim($localeBoolean) . '$2';
            }
        }

        return self::translateFormula(
            self::$functionReplaceFromExcel,
            self::$functionReplaceToLocale,
            $formula,
            ',',
            self::$localeArgumentSeparator
        );
    }

    
    protected static ?array $functionReplaceFromLocale;

    
    protected static ?array $functionReplaceToExcel;

    public function translateFormulaToEnglish(string $formula): string
    {
        if (self::$functionReplaceFromLocale === null) {
            self::$functionReplaceFromLocale = [];
            foreach (self::$localeFunctions as $localeFunctionName) {
                self::$functionReplaceFromLocale[] = '/(@?[^\w\.])' . preg_quote($localeFunctionName, '/') . '([\s]*\()/ui';
            }
            foreach (self::$localeBoolean as $excelBoolean) {
                self::$functionReplaceFromLocale[] = '/(@?[^\w\.])' . preg_quote($excelBoolean, '/') . '([^\w\.])/ui';
            }
        }

        if (self::$functionReplaceToExcel === null) {
            self::$functionReplaceToExcel = [];
            foreach (array_keys(self::$localeFunctions) as $excelFunctionName) {
                self::$functionReplaceToExcel[] = '$1' . trim($excelFunctionName) . '$2';
            }
            foreach (array_keys(self::$localeBoolean) as $excelBoolean) {
                self::$functionReplaceToExcel[] = '$1' . trim($excelBoolean) . '$2';
            }
        }

        return self::translateFormula(self::$functionReplaceFromLocale, self::$functionReplaceToExcel, $formula, self::$localeArgumentSeparator, ',');
    }

    public static function localeFunc(string $function): string
    {
        if (self::$localeLanguage !== 'en_us') {
            $functionName = trim($function, '(');
            if (isset(self::$localeFunctions[$functionName])) {
                $brace = ($functionName != $function);
                $function = self::$localeFunctions[$functionName];
                if ($brace) {
                    $function .= '(';
                }
            }
        }

        return $function;
    }
}
