<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class Protection extends Supervisor
{
    
    const PROTECTION_INHERIT = 'inherit';
    const PROTECTION_PROTECTED = 'protected';
    const PROTECTION_UNPROTECTED = 'unprotected';

    
    protected ?string $locked = null;

    
    protected ?string $hidden = null;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        if (!$isConditional) {
            $this->locked = self::PROTECTION_INHERIT;
            $this->hidden = self::PROTECTION_INHERIT;
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        return $parent->getSharedComponent()->getProtection();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['protection' => $array];
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['locked'])) {
                $this->setLocked($styleArray['locked']);
            }
            if (isset($styleArray['hidden'])) {
                $this->setHidden($styleArray['hidden']);
            }
        }

        return $this;
    }

    
    public function getLocked(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getLocked();
        }

        return $this->locked;
    }

    
    public function setLocked(string $lockType): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['locked' => $lockType]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->locked = $lockType;
        }

        return $this;
    }

    
    public function getHidden(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHidden();
        }

        return $this->hidden;
    }

    
    public function setHidden(string $hiddenType): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['hidden' => $hiddenType]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->hidden = $hiddenType;
        }

        return $this;
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->locked
            . $this->hidden
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'locked', $this->getLocked());
        $this->exportArray2($exportedArray, 'hidden', $this->getHidden());

        return $exportedArray;
    }
}
