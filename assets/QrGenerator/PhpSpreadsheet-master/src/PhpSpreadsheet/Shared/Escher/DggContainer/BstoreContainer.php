<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer;

class BstoreContainer
{
    
    private array $BSECollection = [];

    
    public function addBSE(BstoreContainer\BSE $BSE): void
    {
        $this->BSECollection[] = $BSE;
        $BSE->setParent($this);
    }

    
    public function getBSECollection(): array
    {
        return $this->BSECollection;
    }
}
