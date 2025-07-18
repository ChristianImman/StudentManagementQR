<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;

use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;

class SpContainer
{
    
    private SpgrContainer $parent;

    
    private bool $spgr = false;

    
    private int $spType;

    
    private int $spFlag;

    
    private int $spId;

    
    private array $OPT = [];

    
    private string $startCoordinates = '';

    
    private int|float $startOffsetX;

    
    private int|float $startOffsetY;

    
    private string $endCoordinates;

    
    private int|float $endOffsetX;

    
    private int|float $endOffsetY;

    
    public function setParent(SpgrContainer $parent): void
    {
        $this->parent = $parent;
    }

    
    public function getParent(): SpgrContainer
    {
        return $this->parent;
    }

    
    public function setSpgr(bool $value): void
    {
        $this->spgr = $value;
    }

    
    public function getSpgr(): bool
    {
        return $this->spgr;
    }

    
    public function setSpType(int $value): void
    {
        $this->spType = $value;
    }

    
    public function getSpType(): int
    {
        return $this->spType;
    }

    
    public function setSpFlag(int $value): void
    {
        $this->spFlag = $value;
    }

    
    public function getSpFlag(): int
    {
        return $this->spFlag;
    }

    
    public function setSpId(int $value): void
    {
        $this->spId = $value;
    }

    
    public function getSpId(): int
    {
        return $this->spId;
    }

    
    public function setOPT(int $property, mixed $value): void
    {
        $this->OPT[$property] = $value;
    }

    
    public function getOPT(int $property): mixed
    {
        if (isset($this->OPT[$property])) {
            return $this->OPT[$property];
        }

        return null;
    }

    
    public function getOPTCollection(): array
    {
        return $this->OPT;
    }

    
    public function setStartCoordinates(string $value): void
    {
        $this->startCoordinates = $value;
    }

    
    public function getStartCoordinates(): string
    {
        return $this->startCoordinates;
    }

    
    public function setStartOffsetX(int|float $startOffsetX): void
    {
        $this->startOffsetX = $startOffsetX;
    }

    
    public function getStartOffsetX(): int|float
    {
        return $this->startOffsetX;
    }

    
    public function setStartOffsetY(int|float $startOffsetY): void
    {
        $this->startOffsetY = $startOffsetY;
    }

    
    public function getStartOffsetY(): int|float
    {
        return $this->startOffsetY;
    }

    
    public function setEndCoordinates(string $value): void
    {
        $this->endCoordinates = $value;
    }

    
    public function getEndCoordinates(): string
    {
        return $this->endCoordinates;
    }

    
    public function setEndOffsetX(int|float $endOffsetX): void
    {
        $this->endOffsetX = $endOffsetX;
    }

    
    public function getEndOffsetX(): int|float
    {
        return $this->endOffsetX;
    }

    
    public function setEndOffsetY(int|float $endOffsetY): void
    {
        $this->endOffsetY = $endOffsetY;
    }

    
    public function getEndOffsetY(): int|float
    {
        return $this->endOffsetY;
    }

    
    public function getNestingLevel(): int
    {
        $nestingLevel = 0;

        $parent = $this->getParent();
        while ($parent instanceof SpgrContainer) {
            ++$nestingLevel;
            $parent = $parent->getParent();
        }

        return $nestingLevel;
    }
}
