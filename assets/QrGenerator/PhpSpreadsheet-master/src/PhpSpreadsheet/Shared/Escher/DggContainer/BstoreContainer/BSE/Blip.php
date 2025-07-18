<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

class Blip
{
    
    private BSE $parent;

    
    private string $data;

    
    public function getData(): string
    {
        return $this->data;
    }

    
    public function setData(string $data): void
    {
        $this->data = $data;
    }

    
    public function setParent(BSE $parent): void
    {
        $this->parent = $parent;
    }

    
    public function getParent(): BSE
    {
        return $this->parent;
    }
}
