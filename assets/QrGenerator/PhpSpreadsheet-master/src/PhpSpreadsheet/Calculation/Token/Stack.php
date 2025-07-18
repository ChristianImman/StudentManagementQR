<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Token;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\BranchPruner;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Stack
{
    private BranchPruner $branchPruner;

    
    private array $stack = [];

    
    private int $count = 0;

    public function __construct(BranchPruner $branchPruner)
    {
        $this->branchPruner = $branchPruner;
    }

    
    public function count(): int
    {
        return $this->count;
    }

    
    public function push(string $type, mixed $value, ?string $reference = null): void
    {
        $stackItem = $this->getStackItem($type, $value, $reference);
        $this->stack[$this->count++] = $stackItem;

        if ($type === 'Function') {
            $localeFunction = Calculation::localeFunc(StringHelper::convertToString($value));
            if ($localeFunction != $value) {
                $this->stack[($this->count - 1)]['localeValue'] = $localeFunction;
            }
        }
    }

    public function pushStackItem(array $stackItem): void
    {
        $this->stack[$this->count++] = $stackItem;
    }

    public function getStackItem(string $type, mixed $value, ?string $reference = null): array
    {
        $stackItem = [
            'type' => $type,
            'value' => $value,
            'reference' => $reference,
        ];

        
        $storeKey = $this->branchPruner->currentCondition();
        if (isset($storeKey) || $reference === 'NULL') {
            $stackItem['storeKey'] = $storeKey;
        }

        
        $onlyIf = $this->branchPruner->currentOnlyIf();
        if (isset($onlyIf) || $reference === 'NULL') {
            $stackItem['onlyIf'] = $onlyIf;
        }

        
        $onlyIfNot = $this->branchPruner->currentOnlyIfNot();
        if (isset($onlyIfNot) || $reference === 'NULL') {
            $stackItem['onlyIfNot'] = $onlyIfNot;
        }

        return $stackItem;
    }

    
    public function pop(): ?array
    {
        if ($this->count > 0) {
            return $this->stack[--$this->count];
        }

        return null;
    }

    
    public function last(int $n = 1): ?array
    {
        if ($this->count - $n < 0) {
            return null;
        }

        return $this->stack[$this->count - $n];
    }

    
    public function clear(): void
    {
        $this->stack = [];
        $this->count = 0;
    }
}
