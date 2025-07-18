<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Color extends Supervisor
{
    const NAMED_COLORS = [
        'Black',
        'White',
        'Red',
        'Green',
        'Blue',
        'Yellow',
        'Magenta',
        'Cyan',
    ];

    
    const COLOR_BLACK = 'FF000000';
    const COLOR_WHITE = 'FFFFFFFF';
    const COLOR_RED = 'FFFF0000';
    const COLOR_DARKRED = 'FF800000';
    const COLOR_BLUE = 'FF0000FF';
    const COLOR_DARKBLUE = 'FF000080';
    const COLOR_GREEN = 'FF00FF00';
    const COLOR_DARKGREEN = 'FF008000';
    const COLOR_YELLOW = 'FFFFFF00';
    const COLOR_DARKYELLOW = 'FF808000';
    const COLOR_MAGENTA = 'FFFF00FF';
    const COLOR_CYAN = 'FF00FFFF';

    const NAMED_COLOR_TRANSLATIONS = [
        'Black' => self::COLOR_BLACK,
        'White' => self::COLOR_WHITE,
        'Red' => self::COLOR_RED,
        'Green' => self::COLOR_GREEN,
        'Blue' => self::COLOR_BLUE,
        'Yellow' => self::COLOR_YELLOW,
        'Magenta' => self::COLOR_MAGENTA,
        'Cyan' => self::COLOR_CYAN,
    ];

    const VALIDATE_ARGB_SIZE = 8;
    const VALIDATE_RGB_SIZE = 6;
    const VALIDATE_COLOR_6 = '/^[A-F0-9]{6}$/i';
    const VALIDATE_COLOR_8 = '/^[A-F0-9]{8}$/i';

    private const INDEXED_COLORS = [
        1 => 'FF000000', 
        2 => 'FFFFFFFF', 
        3 => 'FFFF0000', 
        4 => 'FF00FF00', 
        5 => 'FF0000FF', 
        6 => 'FFFFFF00', 
        7 => 'FFFF00FF', 
        8 => 'FF00FFFF', 
        9 => 'FF800000', 
        10 => 'FF008000', 
        11 => 'FF000080', 
        12 => 'FF808000', 
        13 => 'FF800080', 
        14 => 'FF008080', 
        15 => 'FFC0C0C0', 
        16 => 'FF808080', 
        17 => 'FF9999FF', 
        18 => 'FF993366', 
        19 => 'FFFFFFCC', 
        20 => 'FFCCFFFF', 
        21 => 'FF660066', 
        22 => 'FFFF8080', 
        23 => 'FF0066CC', 
        24 => 'FFCCCCFF', 
        25 => 'FF000080', 
        26 => 'FFFF00FF', 
        27 => 'FFFFFF00', 
        28 => 'FF00FFFF', 
        29 => 'FF800080', 
        30 => 'FF800000', 
        31 => 'FF008080', 
        32 => 'FF0000FF', 
        33 => 'FF00CCFF', 
        34 => 'FFCCFFFF', 
        35 => 'FFCCFFCC', 
        36 => 'FFFFFF99', 
        37 => 'FF99CCFF', 
        38 => 'FFFF99CC', 
        39 => 'FFCC99FF', 
        40 => 'FFFFCC99', 
        41 => 'FF3366FF', 
        42 => 'FF33CCCC', 
        43 => 'FF99CC00', 
        44 => 'FFFFCC00', 
        45 => 'FFFF9900', 
        46 => 'FFFF6600', 
        47 => 'FF666699', 
        48 => 'FF969696', 
        49 => 'FF003366', 
        50 => 'FF339966', 
        51 => 'FF003300', 
        52 => 'FF333300', 
        53 => 'FF993300', 
        54 => 'FF993366', 
        55 => 'FF333399', 
        56 => 'FF333333', 
    ];

    
    protected ?string $argb = null;

    private bool $hasChanged = false;

    
    public function __construct(string $colorValue = self::COLOR_BLACK, bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        if (!$isConditional) {
            $this->argb = $this->validateColor($colorValue) ?: self::COLOR_BLACK;
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;
        
        $sharedComponent = $parent->getSharedComponent();
        if ($sharedComponent instanceof Fill) {
            if ($this->parentPropertyName === 'endColor') {
                return $sharedComponent->getEndColor();
            }

            return $sharedComponent->getStartColor();
        }

        return $sharedComponent->getColor();
    }

    
    public function getStyleArray(array $array): array
    {
        
        $parent = $this->parent;

        return $parent->getStyleArray([$this->parentPropertyName => $array]);
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['rgb'])) {
                $this->setRGB($styleArray['rgb']);
            }
            if (isset($styleArray['argb'])) {
                $this->setARGB($styleArray['argb']);
            }
        }

        return $this;
    }

    private function validateColor(?string $colorValue): string
    {
        if ($colorValue === null || $colorValue === '') {
            return self::COLOR_BLACK;
        }
        $named = ucfirst(strtolower($colorValue));
        if (array_key_exists($named, self::NAMED_COLOR_TRANSLATIONS)) {
            return self::NAMED_COLOR_TRANSLATIONS[$named];
        }
        if (preg_match(self::VALIDATE_COLOR_8, $colorValue) === 1) {
            return $colorValue;
        }
        if (preg_match(self::VALIDATE_COLOR_6, $colorValue) === 1) {
            return 'FF' . $colorValue;
        }

        return '';
    }

    
    public function getARGB(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getARGB();
        }

        return $this->argb;
    }

    
    public function setARGB(?string $colorValue = self::COLOR_BLACK, bool $nullStringOkay = false): static
    {
        $this->hasChanged = true;
        if (!$nullStringOkay || $colorValue !== '') {
            $colorValue = $this->validateColor($colorValue);
            if ($colorValue === '') {
                return $this;
            }
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['argb' => $colorValue]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->argb = $colorValue;
        }

        return $this;
    }

    
    public function getRGB(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getRGB();
        }

        return substr($this->argb ?? '', 2);
    }

    
    public function setRGB(?string $colorValue = self::COLOR_BLACK): static
    {
        return $this->setARGB($colorValue);
    }

    
    private static function getColourComponent(string $rgbValue, int $offset, bool $hex = true): string|int
    {
        $colour = substr($rgbValue, $offset, 2) ?: '';
        if (preg_match('/^[0-9a-f]{2}$/i', $colour) !== 1) {
            $colour = '00';
        }

        return ($hex) ? $colour : (int) hexdec($colour);
    }

    
    public static function getRed(string $rgbValue, bool $hex = true)
    {
        return self::getColourComponent($rgbValue, strlen($rgbValue) - 6, $hex);
    }

    
    public static function getGreen(string $rgbValue, bool $hex = true)
    {
        return self::getColourComponent($rgbValue, strlen($rgbValue) - 4, $hex);
    }

    
    public static function getBlue(string $rgbValue, bool $hex = true)
    {
        return self::getColourComponent($rgbValue, strlen($rgbValue) - 2, $hex);
    }

    
    public static function changeBrightness(string $hexColourValue, float $adjustPercentage): string
    {
        $rgba = (strlen($hexColourValue) === 8);
        $adjustPercentage = max(-1.0, min(1.0, $adjustPercentage));

        
        $red = self::getRed($hexColourValue, false);
        
        $green = self::getGreen($hexColourValue, false);
        
        $blue = self::getBlue($hexColourValue, false);

        return (($rgba) ? 'FF' : '') . RgbTint::rgbAndTintToRgb($red, $green, $blue, $adjustPercentage);
    }

    
    public static function indexedColor(int $colorIndex, bool $background = false, ?array $palette = null): self
    {
        
        $colorIndex = (int) $colorIndex;

        if (empty($palette)) {
            if (isset(self::INDEXED_COLORS[$colorIndex])) {
                return new self(self::INDEXED_COLORS[$colorIndex]);
            }
        } else {
            if (isset($palette[$colorIndex])) {
                return new self($palette[$colorIndex]);
            }
        }

        return ($background) ? new self(self::COLOR_WHITE) : new self(self::COLOR_BLACK);
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->argb
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'argb', $this->getARGB());

        return $exportedArray;
    }

    public function getHasChanged(): bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->hasChanged;
        }

        return $this->hasChanged;
    }
}
