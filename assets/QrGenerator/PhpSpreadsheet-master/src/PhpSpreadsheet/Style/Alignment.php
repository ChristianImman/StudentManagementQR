<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class Alignment extends Supervisor
{
    
    const HORIZONTAL_GENERAL = 'general';
    const HORIZONTAL_LEFT = 'left';
    const HORIZONTAL_RIGHT = 'right';
    const HORIZONTAL_CENTER = 'center';
    const HORIZONTAL_CENTER_CONTINUOUS = 'centerContinuous';
    const HORIZONTAL_JUSTIFY = 'justify';
    const HORIZONTAL_FILL = 'fill';
    const HORIZONTAL_DISTRIBUTED = 'distributed'; 
    private const HORIZONTAL_CENTER_CONTINUOUS_LC = 'centercontinuous';
    
    const HORIZONTAL_ALIGNMENT_FOR_XLSX = [
        self::HORIZONTAL_LEFT => self::HORIZONTAL_LEFT,
        self::HORIZONTAL_RIGHT => self::HORIZONTAL_RIGHT,
        self::HORIZONTAL_CENTER => self::HORIZONTAL_CENTER,
        self::HORIZONTAL_CENTER_CONTINUOUS => self::HORIZONTAL_CENTER_CONTINUOUS,
        self::HORIZONTAL_JUSTIFY => self::HORIZONTAL_JUSTIFY,
        self::HORIZONTAL_FILL => self::HORIZONTAL_FILL,
        self::HORIZONTAL_DISTRIBUTED => self::HORIZONTAL_DISTRIBUTED,
    ];
    
    const HORIZONTAL_ALIGNMENT_FOR_HTML = [
        self::HORIZONTAL_LEFT => self::HORIZONTAL_LEFT,
        self::HORIZONTAL_RIGHT => self::HORIZONTAL_RIGHT,
        self::HORIZONTAL_CENTER => self::HORIZONTAL_CENTER,
        self::HORIZONTAL_CENTER_CONTINUOUS => self::HORIZONTAL_CENTER,
        self::HORIZONTAL_JUSTIFY => self::HORIZONTAL_JUSTIFY,
        
        self::HORIZONTAL_DISTRIBUTED => self::HORIZONTAL_JUSTIFY,
    ];

    
    const VERTICAL_BOTTOM = 'bottom';
    const VERTICAL_TOP = 'top';
    const VERTICAL_CENTER = 'center';
    const VERTICAL_JUSTIFY = 'justify';
    const VERTICAL_DISTRIBUTED = 'distributed'; 
    
    private const VERTICAL_BASELINE = 'baseline';
    private const VERTICAL_MIDDLE = 'middle';
    private const VERTICAL_SUB = 'sub';
    private const VERTICAL_SUPER = 'super';
    private const VERTICAL_TEXT_BOTTOM = 'text-bottom';
    private const VERTICAL_TEXT_TOP = 'text-top';

    
    const VERTICAL_ALIGNMENT_FOR_XLSX = [
        self::VERTICAL_BOTTOM => self::VERTICAL_BOTTOM,
        self::VERTICAL_TOP => self::VERTICAL_TOP,
        self::VERTICAL_CENTER => self::VERTICAL_CENTER,
        self::VERTICAL_JUSTIFY => self::VERTICAL_JUSTIFY,
        self::VERTICAL_DISTRIBUTED => self::VERTICAL_DISTRIBUTED,
        
        self::VERTICAL_BASELINE => self::VERTICAL_BOTTOM,
        self::VERTICAL_MIDDLE => self::VERTICAL_CENTER,
        self::VERTICAL_SUB => self::VERTICAL_BOTTOM,
        self::VERTICAL_SUPER => self::VERTICAL_TOP,
        self::VERTICAL_TEXT_BOTTOM => self::VERTICAL_BOTTOM,
        self::VERTICAL_TEXT_TOP => self::VERTICAL_TOP,
    ];

    
    const VERTICAL_ALIGNMENT_FOR_HTML = [
        self::VERTICAL_BOTTOM => self::VERTICAL_BOTTOM,
        self::VERTICAL_TOP => self::VERTICAL_TOP,
        self::VERTICAL_CENTER => self::VERTICAL_MIDDLE,
        self::VERTICAL_JUSTIFY => self::VERTICAL_MIDDLE,
        self::VERTICAL_DISTRIBUTED => self::VERTICAL_MIDDLE,
        
        self::VERTICAL_BASELINE => self::VERTICAL_BASELINE,
        self::VERTICAL_MIDDLE => self::VERTICAL_MIDDLE,
        self::VERTICAL_SUB => self::VERTICAL_SUB,
        self::VERTICAL_SUPER => self::VERTICAL_SUPER,
        self::VERTICAL_TEXT_BOTTOM => self::VERTICAL_TEXT_BOTTOM,
        self::VERTICAL_TEXT_TOP => self::VERTICAL_TEXT_TOP,
    ];

    
    const READORDER_CONTEXT = 0;
    const READORDER_LTR = 1;
    const READORDER_RTL = 2;

    
    const TEXTROTATION_STACK_EXCEL = 255;
    const TEXTROTATION_STACK_PHPSPREADSHEET = -165; 

    
    protected ?string $horizontal = self::HORIZONTAL_GENERAL;

    
    protected ?bool $justifyLastLine = null;

    
    protected ?string $vertical = self::VERTICAL_BOTTOM;

    
    protected ?int $textRotation = 0;

    
    protected bool $wrapText = false;

    
    protected bool $shrinkToFit = false;

    
    protected int $indent = 0;

    
    protected int $readOrder = 0;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        if ($isConditional) {
            $this->horizontal = null;
            $this->vertical = null;
            $this->textRotation = null;
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        return $parent->getSharedComponent()->getAlignment();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['alignment' => $array];
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())
                ->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['horizontal'])) {
                $this->setHorizontal($styleArray['horizontal']);
            }
            if (isset($styleArray['justifyLastLine'])) {
                $this->setJustifyLastLine($styleArray['justifyLastLine']);
            }
            if (isset($styleArray['vertical'])) {
                $this->setVertical($styleArray['vertical']);
            }
            if (isset($styleArray['textRotation'])) {
                $this->setTextRotation($styleArray['textRotation']);
            }
            if (isset($styleArray['wrapText'])) {
                $this->setWrapText($styleArray['wrapText']);
            }
            if (isset($styleArray['shrinkToFit'])) {
                $this->setShrinkToFit($styleArray['shrinkToFit']);
            }
            if (isset($styleArray['indent'])) {
                $this->setIndent($styleArray['indent']);
            }
            if (isset($styleArray['readOrder'])) {
                $this->setReadOrder($styleArray['readOrder']);
            }
        }

        return $this;
    }

    
    public function getHorizontal(): null|string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHorizontal();
        }

        return $this->horizontal;
    }

    
    public function setHorizontal(string $horizontalAlignment): static
    {
        $horizontalAlignment = strtolower($horizontalAlignment);
        if ($horizontalAlignment === self::HORIZONTAL_CENTER_CONTINUOUS_LC) {
            $horizontalAlignment = self::HORIZONTAL_CENTER_CONTINUOUS;
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['horizontal' => $horizontalAlignment]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->horizontal = $horizontalAlignment;
        }

        return $this;
    }

    
    public function getJustifyLastLine(): ?bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getJustifyLastLine();
        }

        return $this->justifyLastLine;
    }

    
    public function setJustifyLastLine(bool $justifyLastLine): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['justifyLastLine' => $justifyLastLine]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->justifyLastLine = $justifyLastLine;
        }

        return $this;
    }

    
    public function getVertical(): null|string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getVertical();
        }

        return $this->vertical;
    }

    
    public function setVertical(string $verticalAlignment): static
    {
        $verticalAlignment = strtolower($verticalAlignment);

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['vertical' => $verticalAlignment]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->vertical = $verticalAlignment;
        }

        return $this;
    }

    
    public function getTextRotation(): null|int
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getTextRotation();
        }

        return $this->textRotation;
    }

    
    public function setTextRotation(int $angleInDegrees): static
    {
        
        if ($angleInDegrees == self::TEXTROTATION_STACK_EXCEL) {
            $angleInDegrees = self::TEXTROTATION_STACK_PHPSPREADSHEET;
        }

        
        if (($angleInDegrees >= -90 && $angleInDegrees <= 90) || $angleInDegrees == self::TEXTROTATION_STACK_PHPSPREADSHEET) {
            if ($this->isSupervisor) {
                $styleArray = $this->getStyleArray(['textRotation' => $angleInDegrees]);
                $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
            } else {
                $this->textRotation = $angleInDegrees;
            }
        } else {
            throw new PhpSpreadsheetException('Text rotation should be a value between -90 and 90.');
        }

        return $this;
    }

    
    public function getWrapText(): bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getWrapText();
        }

        return $this->wrapText;
    }

    
    public function setWrapText(bool $wrapped): static
    {
        if ($wrapped == '') {
            $wrapped = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['wrapText' => $wrapped]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->wrapText = $wrapped;
        }

        return $this;
    }

    
    public function getShrinkToFit(): bool
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getShrinkToFit();
        }

        return $this->shrinkToFit;
    }

    
    public function setShrinkToFit(bool $shrink): static
    {
        if ($shrink == '') {
            $shrink = false;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['shrinkToFit' => $shrink]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->shrinkToFit = $shrink;
        }

        return $this;
    }

    
    public function getIndent(): int
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getIndent();
        }

        return $this->indent;
    }

    
    public function setIndent(int $indent): static
    {
        if ($indent > 0) {
            if (
                $this->getHorizontal() != self::HORIZONTAL_GENERAL
                && $this->getHorizontal() != self::HORIZONTAL_LEFT
                && $this->getHorizontal() != self::HORIZONTAL_RIGHT
                && $this->getHorizontal() != self::HORIZONTAL_DISTRIBUTED
            ) {
                $indent = 0; 
            }
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['indent' => $indent]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->indent = $indent;
        }

        return $this;
    }

    
    public function getReadOrder(): int
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getReadOrder();
        }

        return $this->readOrder;
    }

    
    public function setReadOrder(int $readOrder): static
    {
        if ($readOrder < 0 || $readOrder > 2) {
            $readOrder = 0;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['readOrder' => $readOrder]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->readOrder = $readOrder;
        }

        return $this;
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->horizontal
            . (($this->justifyLastLine === null) ? 'null' : ($this->justifyLastLine ? 't' : 'f'))
            . $this->vertical
            . $this->textRotation
            . ($this->wrapText ? 't' : 'f')
            . ($this->shrinkToFit ? 't' : 'f')
            . $this->indent
            . $this->readOrder
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'horizontal', $this->getHorizontal());
        $this->exportArray2($exportedArray, 'justifyLastLine', $this->getJustifyLastLine());
        $this->exportArray2($exportedArray, 'indent', $this->getIndent());
        $this->exportArray2($exportedArray, 'readOrder', $this->getReadOrder());
        $this->exportArray2($exportedArray, 'shrinkToFit', $this->getShrinkToFit());
        $this->exportArray2($exportedArray, 'textRotation', $this->getTextRotation());
        $this->exportArray2($exportedArray, 'vertical', $this->getVertical());
        $this->exportArray2($exportedArray, 'wrapText', $this->getWrapText());

        return $exportedArray;
    }
}
