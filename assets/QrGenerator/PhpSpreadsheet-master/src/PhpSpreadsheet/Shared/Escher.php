<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

class Escher
{
    
    private ?Escher\DggContainer $dggContainer = null;

    
    private ?Escher\DgContainer $dgContainer = null;

    
    public function getDggContainer(): ?Escher\DggContainer
    {
        return $this->dggContainer;
    }

    
    public function setDggContainer(Escher\DggContainer $dggContainer): Escher\DggContainer
    {
        return $this->dggContainer = $dggContainer;
    }

    
    public function getDgContainer(): ?Escher\DgContainer
    {
        return $this->dgContainer;
    }

    
    public function setDgContainer(Escher\DgContainer $dgContainer): Escher\DgContainer
    {
        return $this->dgContainer = $dgContainer;
    }
}
