<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class Border extends Supervisor
{
    
    const BORDER_NONE = 'none';
    const BORDER_DASHDOT = 'dashDot';
    const BORDER_DASHDOTDOT = 'dashDotDot';
    const BORDER_DASHED = 'dashed';
    const BORDER_DOTTED = 'dotted';
    const BORDER_DOUBLE = 'double';
    const BORDER_HAIR = 'hair';
    const BORDER_MEDIUM = 'medium';
    const BORDER_MEDIUMDASHDOT = 'mediumDashDot';
    const BORDER_MEDIUMDASHDOTDOT = 'mediumDashDotDot';
    const BORDER_MEDIUMDASHED = 'mediumDashed';
    const BORDER_SLANTDASHDOT = 'slantDashDot';
    const BORDER_THICK = 'thick';
    const BORDER_THIN = 'thin';
    const BORDER_OMIT = 'omit'; 

    
    protected string $borderStyle = self::BORDER_NONE;

    
    protected Color $color;

    public ?int $colorIndex = null;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        $this->color = new Color(Color::COLOR_BLACK, $isSupervisor);

        
        if ($isSupervisor) {
            $this->color->bindParent($this, 'color');
        }
        if ($isConditional) {
            $this->borderStyle = self::BORDER_OMIT;
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        
        $sharedComponent = $parent->getSharedComponent();

        return match ($this->parentPropertyName) {
            'bottom' => $sharedComponent->getBottom(),
            'diagonal' => $sharedComponent->getDiagonal(),
            'left' => $sharedComponent->getLeft(),
            'right' => $sharedComponent->getRight(),
            'top' => $sharedComponent->getTop(),
            default => throw new PhpSpreadsheetException('Cannot get shared component for a pseudo-border.'),
        };
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
            if (isset($styleArray['borderStyle'])) {
                $this->setBorderStyle($styleArray['borderStyle']);
            }
            if (isset($styleArray['color'])) {
                $this->getColor()->applyFromArray($styleArray['color']);
            }
        }

        return $this;
    }

    
    public function getBorderStyle(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getBorderStyle();
        }

        return $this->borderStyle;
    }

    
    public function setBorderStyle(bool|string $style): static
    {
        if (empty($style)) {
            $style = self::BORDER_NONE;
        } elseif (is_bool($style)) {
            $style = self::BORDER_MEDIUM;
        }

        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['borderStyle' => $style]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->borderStyle = $style;
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

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->borderStyle
            . $this->color->getHashCode()
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'borderStyle', $this->getBorderStyle());
        $this->exportArray2($exportedArray, 'color', $this->getColor());

        return $exportedArray;
    }
}
