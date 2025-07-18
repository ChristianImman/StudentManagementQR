<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engine;

class Logger
{
    
    private bool $writeDebugLog = false;

    
    private bool $echoDebugLog = false;

    
    private array $debugLog = [];

    
    private CyclicReferenceStack $cellStack;

    
    public function __construct(CyclicReferenceStack $stack)
    {
        $this->cellStack = $stack;
    }

    
    public function setWriteDebugLog(bool $writeDebugLog): void
    {
        $this->writeDebugLog = $writeDebugLog;
    }

    
    public function getWriteDebugLog(): bool
    {
        return $this->writeDebugLog;
    }

    
    public function setEchoDebugLog(bool $echoDebugLog): void
    {
        $this->echoDebugLog = $echoDebugLog;
    }

    
    public function getEchoDebugLog(): bool
    {
        return $this->echoDebugLog;
    }

    
    public function writeDebugLog(string $message, mixed ...$args): void
    {
        
        if ($this->writeDebugLog) {
            $message = sprintf($message, ...$args); /
    public function mergeDebugLog(array $args): void
    {
        if ($this->writeDebugLog) {
            foreach ($args as $entry) {
                $this->writeDebugLog($entry);
            }
        }
    }

    
    public function clearLog(): void
    {
        $this->debugLog = [];
    }

    
    public function getLog(): array
    {
        return $this->debugLog;
    }
}
