<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class Borders extends Supervisor
{
    
    const DIAGONAL_NONE = 0;
    const DIAGONAL_UP = 1;
    const DIAGONAL_DOWN = 2;
    const DIAGONAL_BOTH = 3;

    
    protected Border $left;

    
    protected Border $right;

    
    protected Border $top;

    
    protected Border $bottom;

    
    protected Border $diagonal;

    
    protected int $diagonalDirection;

    
    protected Border $allBorders;

    
    protected Border $outline;

    
    protected Border $inside;

    
    protected Border $vertical;

    
    protected Border $horizontal;

    
    public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
        
        parent::__construct($isSupervisor);

        
        $this->left = new Border($isSupervisor, $isConditional);
        $this->right = new Border($isSupervisor, $isConditional);
        $this->top = new Border($isSupervisor, $isConditional);
        $this->bottom = new Border($isSupervisor, $isConditional);
        $this->diagonal = new Border($isSupervisor, $isConditional);
        $this->diagonalDirection = self::DIAGONAL_NONE;

        
        if ($isSupervisor) {
            
            $this->allBorders = new Border(true, $isConditional);
            $this->outline = new Border(true, $isConditional);
            $this->inside = new Border(true, $isConditional);
            $this->vertical = new Border(true, $isConditional);
            $this->horizontal = new Border(true, $isConditional);

            
            $this->left->bindParent($this, 'left');
            $this->right->bindParent($this, 'right');
            $this->top->bindParent($this, 'top');
            $this->bottom->bindParent($this, 'bottom');
            $this->diagonal->bindParent($this, 'diagonal');
            $this->allBorders->bindParent($this, 'allBorders');
            $this->outline->bindParent($this, 'outline');
            $this->inside->bindParent($this, 'inside');
            $this->vertical->bindParent($this, 'vertical');
            $this->horizontal->bindParent($this, 'horizontal');
        }
    }

    
    public function getSharedComponent(): self
    {
        
        $parent = $this->parent;

        return $parent->getSharedComponent()->getBorders();
    }

    
    public function getStyleArray(array $array): array
    {
        return ['borders' => $array];
    }

    
    public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['left'])) {
                $this->getLeft()->applyFromArray($styleArray['left']);
            }
            if (isset($styleArray['right'])) {
                $this->getRight()->applyFromArray($styleArray['right']);
            }
            if (isset($styleArray['top'])) {
                $this->getTop()->applyFromArray($styleArray['top']);
            }
            if (isset($styleArray['bottom'])) {
                $this->getBottom()->applyFromArray($styleArray['bottom']);
            }
            if (isset($styleArray['diagonal'])) {
                $this->getDiagonal()->applyFromArray($styleArray['diagonal']);
            }
            if (isset($styleArray['diagonalDirection'])) {
                $this->setDiagonalDirection($styleArray['diagonalDirection']);
            }
            if (isset($styleArray['allBorders'])) {
                $this->getLeft()->applyFromArray($styleArray['allBorders']);
                $this->getRight()->applyFromArray($styleArray['allBorders']);
                $this->getTop()->applyFromArray($styleArray['allBorders']);
                $this->getBottom()->applyFromArray($styleArray['allBorders']);
            }
        }

        return $this;
    }

    
    public function getLeft(): Border
    {
        return $this->left;
    }

    
    public function getRight(): Border
    {
        return $this->right;
    }

    
    public function getTop(): Border
    {
        return $this->top;
    }

    
    public function getBottom(): Border
    {
        return $this->bottom;
    }

    
    public function getDiagonal(): Border
    {
        return $this->diagonal;
    }

    
    public function getAllBorders(): Border
    {
        if (!$this->isSupervisor) {
            throw new PhpSpreadsheetException('Can only get pseudo-border for supervisor.');
        }

        return $this->allBorders;
    }

    
    public function getOutline(): Border
    {
        if (!$this->isSupervisor) {
            throw new PhpSpreadsheetException('Can only get pseudo-border for supervisor.');
        }

        return $this->outline;
    }

    
    public function getInside(): Border
    {
        if (!$this->isSupervisor) {
            throw new PhpSpreadsheetException('Can only get pseudo-border for supervisor.');
        }

        return $this->inside;
    }

    
    public function getVertical(): Border
    {
        if (!$this->isSupervisor) {
            throw new PhpSpreadsheetException('Can only get pseudo-border for supervisor.');
        }

        return $this->vertical;
    }

    
    public function getHorizontal(): Border
    {
        if (!$this->isSupervisor) {
            throw new PhpSpreadsheetException('Can only get pseudo-border for supervisor.');
        }

        return $this->horizontal;
    }

    
    public function getDiagonalDirection(): int
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getDiagonalDirection();
        }

        return $this->diagonalDirection;
    }

    
    public function setDiagonalDirection(int $direction): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['diagonalDirection' => $direction]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->diagonalDirection = $direction;
        }

        return $this;
    }

    
    public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashcode();
        }

        return md5(
            $this->getLeft()->getHashCode()
            . $this->getRight()->getHashCode()
            . $this->getTop()->getHashCode()
            . $this->getBottom()->getHashCode()
            . $this->getDiagonal()->getHashCode()
            . $this->getDiagonalDirection()
            . __CLASS__
        );
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'bottom', $this->getBottom());
        $this->exportArray2($exportedArray, 'diagonal', $this->getDiagonal());
        $this->exportArray2($exportedArray, 'diagonalDirection', $this->getDiagonalDirection());
        $this->exportArray2($exportedArray, 'left', $this->getLeft());
        $this->exportArray2($exportedArray, 'right', $this->getRight());
        $this->exportArray2($exportedArray, 'top', $this->getTop());

        return $exportedArray;
    }
}
