<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Fill extends Supervisor
{
    
    const FILL_NONE = 'none';
    const FILL_SOLID = 'solid';
    const FILL_GRADIENT_LINEAR = 'linear';
    const FILL_GRADIENT_PATH = 'path';
    const FILL_PATTERN_DARKDOWN = 'darkDown';
    const FILL_PATTERN_DARKGRAY = 'darkGray';
    const FILL_PATTERN_DARKGRID = 'darkGrid';
    const FILL_PATTERN_DARKHORIZONTAL = 'darkHorizontal';
    const FILL_PATTERN_DARKTRELLIS = 'darkTrellis';
    const FILL_PATTERN_DARKUP = 'darkUp';
    const FILL_PATTERN_DARKVERTICAL = 'darkVertical';
    const FILL_PATTERN_GRAY0625 = 'gray0625';
    const FILL_PATTERN_GRAY125 = 'gray125';
    const FILL_PATTERN_LIGHTDOWN = 'lightDown';
    const FILL_PATTERN_LIGHTGRAY = 'lightGray';
    const FILL_PATTERN_LIGHTGRID = 'lightGrid';
    const FILL_PATTERN_LIGHTHORIZONTAL = 'lightHorizontal';
    const FILL_PATTERN_LIGHTTRELLIS = 'lightTrellis';
    const FILL_PATTERN_LIGHTUP = 'lightUp';
    const FILL_PATTERN_LIGHTVERTICAL = 'lightVertical';
    const FILL_PATTERN_MEDIUMGRAY = 'mediumGray';

    public ?int $startcolorIndex = null;

    public ?int $endcolorIndex = null;

    
    protected ?string $fillType = self::FILL_NONE;

    
    protected float $rotation = 0.0;

    
    protected Color $startColor;

    
    protected Color $endColor;

    private bool $colorChanged = false;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        if ($isConditional) {
            $this->fillType = null;
        }
        $this->startColor = new Color(Color::COLOR_WHITE, $isSupervisor, $isConditional);
        $this->endColor = new Color(Color::COLOR_BLACK, $isSupervisor, $isConditional);

        
        if ($isSupervisor) {
            $this->startColor->bindParent($this, 'startColor');
            $this->endColor->bindParent($this, 'endColor');
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        return $parent->getSharedComponent()->getFill();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['fill' => $array];
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['fillType'])) {
                $this->setFillType($styleArray['fillType']);
            }
            if (isset($styleArray['rotation'])) {
                $this->setRotation($styleArray['rotation']);
            }
            if (isset($styleArray['startColor'])) {
                $this->getStartColor()->applyFromArray($styleArray['startColor']);
            }
            if (isset($styleArray['endColor'])) {
                $this->getEndColor()->applyFromArray($styleArray['endColor']);
            }
            if (isset($styleArray['color'])) {
                $this->getStartColor()->applyFromArray($styleArray['color']);
                $this->getEndColor()->applyFromArray($styleArray['color']);
            }
        }

        return $this;
    }

    
    public function getFillType(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getFillType();
        }

        return $this->fillType;
    }

    
    public function setFillType(string $fillType): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['fillType' => $fillType]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->fillType = $fillType;
        }

        return $this;
    }

    
    public function getRotation(): float
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getRotation();
        }

        return $this->rotation;
    }

    
    public function setRotation(float $angleInDegrees): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['rotation' => $angleInDegrees]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->rotation = $angleInDegrees;
        }

        return $this;
    }

    
    public function getStartColor(): Color
    {
        return $this->startColor;
    }

    
    public function setStartColor(Color $color): static
    {
        $this->colorChanged = true;
        
        $color = $color->getIsSupervisor() ? $color->getSharedComponent() : $color;

        if ($this->isSupervisor) {
            $styleArray = $this->getStartColor()->getStyleArray(['argb' => $color->getARGB()]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->startColor = $color;
        }

        return $this;
    }

    
    public function getEndColor(): Color
    {
        return $this->endColor;
    }

    
    public function setEndColor(Color $color): static
    {
        $this->colorChanged = true;
        
        $color = $color->getIsSupervisor() ? $color->getSharedComponent() : $color;

        if ($this->isSupervisor) {
            $styleArray = $this->getEndColor()->getStyleArray(['argb' => $color->getARGB()]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->endColor = $color;
        }

        return $this;
    }

    public function getColorsChanged(): bool
    {
        if ($this->isSupervisor) {
            $changed = $this->getSharedComponent()->colorChanged;
        } else {
            $changed = $this->colorChanged;
        }

        return $changed || $this->startColor->getHasChanged() || $this->endColor->getHasChanged();
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        
        
        return md5(
            $this->getFillType()
            . $this->getRotation()
            . ($this->getFillType() !== self::FILL_NONE ? $this->getStartColor()->getHashCode() : '')
            . ($this->getFillType() !== self::FILL_NONE ? $this->getEndColor()->getHashCode() : '')
            . ((string) $this->getColorsChanged())
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'fillType', $this->getFillType());
        $this->exportArray2($exportedArray, 'rotation', $this->getRotation());
        if ($this->getColorsChanged()) {
            $this->exportArray2($exportedArray, 'endColor', $this->getEndColor());
            $this->exportArray2($exportedArray, 'startColor', $this->getStartColor());
        }

        return $exportedArray;
    }
}
