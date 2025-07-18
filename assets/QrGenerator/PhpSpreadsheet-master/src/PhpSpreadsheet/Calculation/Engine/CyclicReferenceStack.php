<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engine;

class CyclicReferenceStack
{
    
    private array $stack = [];

    
    public function count(): int
    {
        return count($this->stack);
    }

    
    public function push(mixed $value): void
    {
        $this->stack[$value] = $value;
    }

    
    public function pop(): mixed
    {
        return array_pop($this->stack);
    }

    
    public function onStack(mixed $value): bool
    {
        return isset($this->stack[$value]);
    }

    
    public function clear(): void
    {
        $this->stack = [];
    }

    
    public function showStack(): array
    {
        return $this->stack;
    }
}
