<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engine;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;

class BranchPruner
{
    protected bool $branchPruningEnabled;

    
    private int $branchStoreKeyCounter = 0;

    
    protected ?string $pendingStoreKey = null;

    
    protected array $storeKeysStack = [];

    
    protected array $conditionMap = [];

    
    protected array $thenMap = [];

    
    protected array $elseMap = [];

    
    protected array $braceDepthMap = [];

    protected ?string $currentCondition = null;

    protected ?string $currentOnlyIf = null;

    protected ?string $currentOnlyIfNot = null;

    protected ?string $previousStoreKey = null;

    public function __construct(bool $branchPruningEnabled)
    {
        $this->branchPruningEnabled = $branchPruningEnabled;
    }

    public function clearBranchStore(): void
    {
        $this->branchStoreKeyCounter = 0;
    }

    public function initialiseForLoop(): void
    {
        $this->currentCondition = null;
        $this->currentOnlyIf = null;
        $this->currentOnlyIfNot = null;
        $this->previousStoreKey = null;
        $this->pendingStoreKey = empty($this->storeKeysStack) ? null : end($this->storeKeysStack);

        if ($this->branchPruningEnabled) {
            $this->initialiseCondition();
            $this->initialiseThen();
            $this->initialiseElse();
        }
    }

    private function initialiseCondition(): void
    {
        if (isset($this->conditionMap[$this->pendingStoreKey]) && $this->conditionMap[$this->pendingStoreKey]) {
            $this->currentCondition = $this->pendingStoreKey;
            $stackDepth = count($this->storeKeysStack);
            if ($stackDepth > 1) {
                
                $this->previousStoreKey = $this->storeKeysStack[$stackDepth - 2];
            }
        }
    }

    private function initialiseThen(): void
    {
        if (isset($this->thenMap[$this->pendingStoreKey]) && $this->thenMap[$this->pendingStoreKey]) {
            $this->currentOnlyIf = $this->pendingStoreKey;
        } elseif (
            isset($this->previousStoreKey, $this->thenMap[$this->previousStoreKey])
            && $this->thenMap[$this->previousStoreKey]
        ) {
            $this->currentOnlyIf = $this->previousStoreKey;
        }
    }

    private function initialiseElse(): void
    {
        if (isset($this->elseMap[$this->pendingStoreKey]) && $this->elseMap[$this->pendingStoreKey]) {
            $this->currentOnlyIfNot = $this->pendingStoreKey;
        } elseif (
            isset($this->previousStoreKey, $this->elseMap[$this->previousStoreKey])
            && $this->elseMap[$this->previousStoreKey]
        ) {
            $this->currentOnlyIfNot = $this->previousStoreKey;
        }
    }

    public function decrementDepth(): void
    {
        if (!empty($this->pendingStoreKey)) {
            --$this->braceDepthMap[$this->pendingStoreKey];
        }
    }

    public function incrementDepth(): void
    {
        if (!empty($this->pendingStoreKey)) {
            ++$this->braceDepthMap[$this->pendingStoreKey];
        }
    }

    public function functionCall(string $functionName): void
    {
        if ($this->branchPruningEnabled && ($functionName === 'IF(')) {
            
            $this->pendingStoreKey = $this->getUnusedBranchStoreKey();
            $this->storeKeysStack[] = $this->pendingStoreKey;
            $this->conditionMap[$this->pendingStoreKey] = true;
            $this->braceDepthMap[$this->pendingStoreKey] = 0;
        } elseif (!empty($this->pendingStoreKey) && array_key_exists($this->pendingStoreKey, $this->braceDepthMap)) {
            
            ++$this->braceDepthMap[$this->pendingStoreKey];
        }
    }

    public function argumentSeparator(): void
    {
        if (!empty($this->pendingStoreKey) && $this->braceDepthMap[$this->pendingStoreKey] === 0) {
            
            if ($this->conditionMap[$this->pendingStoreKey]) {
                $this->conditionMap[$this->pendingStoreKey] = false;
                $this->thenMap[$this->pendingStoreKey] = true;
            } elseif ($this->thenMap[$this->pendingStoreKey]) {
                $this->thenMap[$this->pendingStoreKey] = false;
                $this->elseMap[$this->pendingStoreKey] = true;
            } elseif ($this->elseMap[$this->pendingStoreKey]) {
                throw new Exception('Reaching fourth argument of an IF');
            }
        }
    }

    public function closingBrace(mixed $value): void
    {
        if (!empty($this->pendingStoreKey) && $this->braceDepthMap[$this->pendingStoreKey] === -1) {
            
            if ($value !== 'IF(') {
                throw new Exception('Parser bug we should be in an "IF("');
            }

            if ($this->conditionMap[$this->pendingStoreKey]) {
                throw new Exception('We should not be expecting a condition');
            }

            $this->thenMap[$this->pendingStoreKey] = false;
            $this->elseMap[$this->pendingStoreKey] = false;
            --$this->braceDepthMap[$this->pendingStoreKey];
            array_pop($this->storeKeysStack);
            $this->pendingStoreKey = null;
        }
    }

    public function currentCondition(): ?string
    {
        return $this->currentCondition;
    }

    public function currentOnlyIf(): ?string
    {
        return $this->currentOnlyIf;
    }

    public function currentOnlyIfNot(): ?string
    {
        return $this->currentOnlyIfNot;
    }

    private function getUnusedBranchStoreKey(): string
    {
        $storeKeyValue = 'storeKey-' . $this->branchStoreKeyCounter;
        ++$this->branchStoreKeyCounter;

        return $storeKeyValue;
    }
}
