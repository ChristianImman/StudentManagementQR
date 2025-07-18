<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer;

class SpgrContainer
{
    
    private ?self $parent = null;

    
    private array $children = [];

    
    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    
    public function getParent(): ?self
    {
        return $this->parent;
    }

    
    public function addChild(mixed $child): void
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    
    public function getChildren(): array
    {
        return $this->children;
    }

    
    public function getAllSpContainers(): array
    {
        $allSpContainers = [];

        foreach ($this->children as $child) {
            if ($child instanceof self) {
                $allSpContainers = array_merge($allSpContainers, $child->getAllSpContainers());
            } else {
                $allSpContainers[] = $child;
            }
        }

        return $allSpContainers;
    }
}
