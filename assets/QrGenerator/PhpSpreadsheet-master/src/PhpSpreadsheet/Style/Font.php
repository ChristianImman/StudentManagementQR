<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\Chart\ChartColor;

class Font extends Supervisor
{
    
    const UNDERLINE_NONE = 'none';
    const UNDERLINE_DOUBLE = 'double';
    const UNDERLINE_DOUBLEACCOUNTING = 'doubleAccounting';
    const UNDERLINE_SINGLE = 'single';
    const UNDERLINE_SINGLEACCOUNTING = 'singleAccounting';

    const CAP_ALL = 'all';
    const CAP_SMALL = 'small';
    const CAP_NONE = 'none';
    private const VALID_CAPS = [self::CAP_ALL, self::CAP_SMALL, self::CAP_NONE];

    protected ?string $cap = null;

    public const DEFAULT_FONT_NAME = 'Calibri';

    
    protected ?string $name = self::DEFAULT_FONT_NAME;

    
    private string $latin = '';

    private string $eastAsian = '';

    private string $complexScript = '';

    private int $baseLine = 0;

    private string $strikeType = '';

    private ?ChartColor $underlineColor = null;

    private ?ChartColor $chartColor = null;
    

    
    protected ?float $size = 11;

    
    protected ?bool $bold = false;

    
    protected ?bool $italic = false;

    
    protected ?bool $superscript = false;

    
    protected ?bool $subscript = false;

    
    protected ?string $underline = self::UNDERLINE_NONE;

    
    protected ?bool $strikethrough = false;

    
    protected Color $color;

    public ?int $colorIndex = null;

    protected string $scheme = '';

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        if ($isConditional) {
            $this->name = null;
            $this->size = null;
            $this->bold = null;
            $this->italic = null;
            $this->superscript = null;
            $this->subscript = null;
            $this->underline = null;
            $this->strikethrough = null;
            $this->color = new Color(Color::COLOR_BLACK, $isSupervisor, $isConditional);
        } else {
            $this->color = new Color(Color::COLOR_BLACK, $isSupervisor);
        }
        
        if ($isSupervisor) {
            $this->color->bindParent($this, 'color');
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        return $parent->getSharedComponent()->getFont();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['font' => $array];
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['name'])) {
                $this->setName($styleArray['name']);
            }
            if (isset($styleArray['latin'])) {
                $this->setLatin($styleArray['latin']);
            }
            if (isset($styleArray['eastAsian'])) {
                $this->setEastAsian($styleArray['eastAsian']);
            }
            if (isset($styleArray['complexScript'])) {
                $this->setComplexScript($styleArray['complexScript']);
            }
            if (isset($styleArray['bold'])) {
                $this->setBold($styleArray['bold']);
            }
            if (isset($styleArray['italic'])) {
                $this->setItalic($styleArray['italic']);
            }
            if (isset($styleArray['superscript'])) {
                $this->setSuperscript($styleArray['superscript']);
            }
            if (isset($styleArray['subscript'])) {
                $this->setSubscript($styleArray['subscript']);
            }
            if (isset($styleArray['underline'])) {
                $this->setUnderline($styleArray['underline']);
            }
            if (isset($styleArray['strikethrough'])) {
                $this->setStrikethrough($styleArray['strikethrough']);
            }
            if (isset($styleArray['color'])) {
                $this->getColor()->applyFromArray($styleArray['color']);
            }
            if (isset($styleArray['size'])) {
                $this->setSize($styleArray['size']);
            }
            if (isset($styleArray['chartColor'])) {
                $this->chartColor = $styleArray['chartColor'];
            }
            if (isset($styleArray['scheme'])) {
                $this->setScheme($styleArray['scheme']);
            }
            if (isset($styleArray['cap'])) {
                $this->setCap($styleArray['cap']);
            }
        }

        return $this;
    }

    
    public function getName(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getName();
        }

        return $this->name;
    }

    public function getLatin(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getLatin();
        }

        return $this->latin;
    }

    public function getEastAsian(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getEastAsian();
        }

        return $this->eastAsian;
    }

    public function getComplexScript(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getComplexScript();
        }

        return $this->complexScript;
    }

    
    public function setName(string $fontname): self
    {
        if ($fontname == '') {
            $fontname = 'Calibri';
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['name' => $fontname]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->name = $fontname;
        }

        return $this->setScheme('');
    }

    public function setLatin(string $fontname): self
    {
        if ($fontname == '') {
            $fontname = 'Calibri';
        }
        if (!$this->isSupervisor) {
            $this->latin = $fontname;
        } else {
            
            
            $styleArray = $this->getStyleArray(['latin' => $fontname]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function setEastAsian(string $fontname): self
    {
        if ($fontname == '') {
            $fontname = 'Calibri';
        }
        if (!$this->isSupervisor) {
            $this->eastAsian = $fontname;
        } else {
            
            
            $styleArray = $this->getStyleArray(['eastAsian' => $fontname]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function setComplexScript(string $fontname): self
    {
        if ($fontname == '') {
            $fontname = 'Calibri';
        }
        if (!$this->isSupervisor) {
            $this->complexScript = $fontname;
        } else {
            
            
            $styleArray = $this->getStyleArray(['complexScript' => $fontname]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    
    public function getSize(): ?float
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getSize();
        }

        return $this->size;
    }

    
    public function setSize(mixed $sizeInPoints, bool $nullOk = false): static
    {
        if (is_string($sizeInPoints) || is_int($sizeInPoints)) {
            $sizeInPoints = (float) $sizeInPoints; 
        }

        
        
        if (!is_float($sizeInPoints) || !($sizeInPoints > 0)) {
            if (!$nullOk || $sizeInPoints !== null) {
                $sizeInPoints = 10.0;
            }
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['size' => $sizeInPoints]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->size = $sizeInPoints;
        }

        return $this;
    }

    
    public function getBold(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getBold();
        }

        return $this->bold;
    }

    
    public function setBold(bool $bold): static
    {
        if ($bold == '') {
            $bold = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['bold' => $bold]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->bold = $bold;
        }

        return $this;
    }

    
    public function getItalic(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getItalic();
        }

        return $this->italic;
    }

    
    public function setItalic(bool $italic): static
    {
        if ($italic == '') {
            $italic = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['italic' => $italic]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->italic = $italic;
        }

        return $this;
    }

    
    public function getSuperscript(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getSuperscript();
        }

        return $this->superscript;
    }

    
    public function setSuperscript(bool $superscript): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['superscript' => $superscript]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->superscript = $superscript;
            if ($this->superscript) {
                $this->subscript = false;
            }
        }

        return $this;
    }

    
    public function getSubscript(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getSubscript();
        }

        return $this->subscript;
    }

    
    public function setSubscript(bool $subscript): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['subscript' => $subscript]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->subscript = $subscript;
            if ($this->subscript) {
                $this->superscript = false;
            }
        }

        return $this;
    }

    public function getBaseLine(): int
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getBaseLine();
        }

        return $this->baseLine;
    }

    public function setBaseLine(int $baseLine): self
    {
        if (!$this->isSupervisor) {
            $this->baseLine = $baseLine;
        } else {
            
            
            $styleArray = $this->getStyleArray(['baseLine' => $baseLine]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function getStrikeType(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getStrikeType();
        }

        return $this->strikeType;
    }

    public function setStrikeType(string $strikeType): self
    {
        if (!$this->isSupervisor) {
            $this->strikeType = $strikeType;
        } else {
            
            
            $styleArray = $this->getStyleArray(['strikeType' => $strikeType]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function getUnderlineColor(): ?ChartColor
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getUnderlineColor();
        }

        return $this->underlineColor;
    }

    public function setUnderlineColor(array $colorArray): self
    {
        if (!$this->isSupervisor) {
            $this->underlineColor = new ChartColor($colorArray);
        } else {
            
            
            $styleArray = $this->getStyleArray(['underlineColor' => $colorArray]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function getChartColor(): ?ChartColor
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getChartColor();
        }

        return $this->chartColor;
    }

    public function setChartColor(array $colorArray): self
    {
        if (!$this->isSupervisor) {
            $this->chartColor = new ChartColor($colorArray);
        } else {
            
            
            $styleArray = $this->getStyleArray(['chartColor' => $colorArray]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            
        }

        return $this;
    }

    public function setChartColorFromObject(?ChartColor $chartColor): self
    {
        $this->chartColor = $chartColor;

        return $this;
    }

    
    public function getUnderline(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getUnderline();
        }

        return $this->underline;
    }

    
    public function setUnderline($underlineStyle): static
    {
        if (is_bool($underlineStyle)) {
            $underlineStyle = ($underlineStyle) ? self::UNDERLINE_SINGLE : self::UNDERLINE_NONE;
        } elseif ($underlineStyle == '') {
            $underlineStyle = self::UNDERLINE_NONE;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['underline' => $underlineStyle]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->underline = $underlineStyle;
        }

        return $this;
    }

    
    public function getStrikethrough(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getStrikethrough();
        }

        return $this->strikethrough;
    }

    
    public function setStrikethrough(bool $strikethru): static
    {
        if ($strikethru == '') {
            $strikethru = false;
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['strikethrough' => $strikethru]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->strikethrough = $strikethru;
        }

        return $this;
    }

    
    public function getColor(): Color
    {
        return $this->color;
    }

    
    public function setColor(Color $color): static
    {
        
        $color = $color->getIsSupervisor() ? $color->getSharedComponent() : $color;

        if ($this->isSupervisor) {
            $styleArray = $this->getColor()->getStyleArray(['argb' => $color->getARGB()]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->color = $color;
        }

        return $this;
    }

    private function hashChartColor(?ChartColor $underlineColor): string
    {
        if ($underlineColor === null) {
            return '';
        }

        return
            $underlineColor->getValue()
            . $underlineColor->getType()
            . (string) $underlineColor->getAlpha();
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->name
            . $this->size
            . ($this->bold ? 't' : 'f')
            . ($this->italic ? 't' : 'f')
            . ($this->superscript ? 't' : 'f')
            . ($this->subscript ? 't' : 'f')
            . $this->underline
            . ($this->strikethrough ? 't' : 'f')
            . $this->color->getHashCode()
            . $this->scheme
            . implode(
                '*',
                [
                    $this->latin,
                    $this->eastAsian,
                    $this->complexScript,
                    $this->strikeType,
                    $this->hashChartColor($this->chartColor),
                    $this->hashChartColor($this->underlineColor),
                    (string) $this->baseLine,
                    (string) $this->cap,
                ]
            )
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'baseLine', $this->getBaseLine());
        $this->exportArray2($exportedArray, 'bold', $this->getBold());
        $this->exportArray2($exportedArray, 'cap', $this->getCap());
        $this->exportArray2($exportedArray, 'chartColor', $this->getChartColor());
        $this->exportArray2($exportedArray, 'color', $this->getColor());
        $this->exportArray2($exportedArray, 'complexScript', $this->getComplexScript());
        $this->exportArray2($exportedArray, 'eastAsian', $this->getEastAsian());
        $this->exportArray2($exportedArray, 'italic', $this->getItalic());
        $this->exportArray2($exportedArray, 'latin', $this->getLatin());
        $this->exportArray2($exportedArray, 'name', $this->getName());
        $this->exportArray2($exportedArray, 'scheme', $this->getScheme());
        $this->exportArray2($exportedArray, 'size', $this->getSize());
        $this->exportArray2($exportedArray, 'strikethrough', $this->getStrikethrough());
        $this->exportArray2($exportedArray, 'strikeType', $this->getStrikeType());
        $this->exportArray2($exportedArray, 'subscript', $this->getSubscript());
        $this->exportArray2($exportedArray, 'superscript', $this->getSuperscript());
        $this->exportArray2($exportedArray, 'underline', $this->getUnderline());
        $this->exportArray2($exportedArray, 'underlineColor', $this->getUnderlineColor());

        return $exportedArray;
    }

    public function getScheme(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getScheme();
        }

        return $this->scheme;
    }

    public function setScheme(string $scheme): self
    {
        if ($scheme === '' || $scheme === 'major' || $scheme === 'minor') {
            if ($this->isSupervisor) {
                $styleArray = $this->getStyleArray(['scheme' => $scheme]);
                $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            } else {
                $this->scheme = $scheme;
            }
        }

        return $this;
    }

    
    public function setCap(string $cap): self
    {
        $this->cap = in_array($cap, self::VALID_CAPS, true) ? $cap : null;

        return $this;
    }

    public function getCap(): ?string
    {
        return $this->cap;
    }

    
    public function __clone()
    {
        $this->color = clone $this->color;
        $this->chartColor = ($this->chartColor === null) ? null : clone $this->chartColor;
        $this->underlineColor = ($this->underlineColor === null) ? null : clone $this->underlineColor;
    }
}
