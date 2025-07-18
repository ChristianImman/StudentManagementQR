<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Theme
{
    
    private string $themeName;

    
    private string $colourSchemeName;

    
    private array $colourMap;

    
    public function __construct(string $themeName, string $colourSchemeName, array $colourMap)
    {
        
        $this->themeName = $themeName;
        $this->colourSchemeName = $colourSchemeName;
        $this->colourMap = $colourMap;
    }

    
    public function getThemeName(): string
    {
        return $this->themeName;
    }

    
    public function getColourSchemeName(): string
    {
        return $this->colourSchemeName;
    }

    
    public function getColourByIndex(int $index): ?string
    {
        return $this->colourMap[$index] ?? null;
    }
}
