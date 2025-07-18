<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher;

class DggContainer
{
    
    private int $spIdMax;

    
    private int $cDgSaved;

    
    private int $cSpSaved;

    
    private ?DggContainer\BstoreContainer $bstoreContainer = null;

    
    private array $OPT = [];

    
    private array $IDCLs = [];

    
    public function getSpIdMax(): int
    {
        return $this->spIdMax;
    }

    
    public function setSpIdMax(int $value): void
    {
        $this->spIdMax = $value;
    }

    
    public function getCDgSaved(): int
    {
        return $this->cDgSaved;
    }

    
    public function setCDgSaved(int $value): void
    {
        $this->cDgSaved = $value;
    }

    
    public function getCSpSaved(): int
    {
        return $this->cSpSaved;
    }

    
    public function setCSpSaved(int $value): void
    {
        $this->cSpSaved = $value;
    }

    
    public function getBstoreContainer(): ?DggContainer\BstoreContainer
    {
        return $this->bstoreContainer;
    }

    
    public function setBstoreContainer(DggContainer\BstoreContainer $bstoreContainer): void
    {
        $this->bstoreContainer = $bstoreContainer;
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

    
    public function getIDCLs(): array
    {
        return $this->IDCLs;
    }

    
    public function setIDCLs(array $IDCLs): void
    {
        $this->IDCLs = $IDCLs;
    }
}
