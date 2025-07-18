<?php

namespace PhpOffice\PhpSpreadsheet;


class HashTable
{
    
    protected array $items = [];

    
    protected array $keyMap = [];

    
    public function __construct(?array $source = [])
    {
        if ($source !== null) {
            
            $this->addFromSource($source);
        }
    }

    
    public function addFromSource(?array $source = null): void
    {
        
        if ($source === null) {
            return;
        }

        foreach ($source as $item) {
            $this->add($item);
        }
    }

    
    public function add(IComparable $source): void
    {
        $hash = $source->getHashCode();
        if (!isset($this->items[$hash])) {
            $this->items[$hash] = $source;
            $this->keyMap[count($this->items) - 1] = $hash;
        }
    }

    
    public function remove(IComparable $source): void
    {
        $hash = $source->getHashCode();
        if (isset($this->items[$hash])) {
            unset($this->items[$hash]);

            $deleteKey = -1;
            foreach ($this->keyMap as $key => $value) {
                if ($deleteKey >= 0) {
                    $this->keyMap[$key - 1] = $value;
                }

                if ($value == $hash) {
                    $deleteKey = $key;
                }
            }
            unset($this->keyMap[count($this->keyMap) - 1]);
        }
    }

    
    public function clear(): void
    {
        $this->items = [];
        $this->keyMap = [];
    }

    
    public function count(): int
    {
        return count($this->items);
    }

    
    public function getIndexForHashCode(string $hashCode): false|int
    {
        return array_search($hashCode, $this->keyMap, true);
    }

    
    public function getByIndex(int $index): ?IComparable
    {
        if (isset($this->keyMap[$index])) {
            return $this->getByHashCode($this->keyMap[$index]);
        }

        return null;
    }

    
    public function getByHashCode(string $hashCode): ?IComparable
    {
        if (isset($this->items[$hashCode])) {
            return $this->items[$hashCode];
        }

        return null;
    }

    
    public function toArray(): array
    {
        return $this->items;
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            
            if (is_array($value)) {
                $array1 = $value;
                foreach ($array1 as $key1 => $value1) {
                    if (is_object($value1)) {
                        $array1[$key1] = clone $value1;
                    }
                }
                $this->$key = $array1;
            }
        }
    }
}
