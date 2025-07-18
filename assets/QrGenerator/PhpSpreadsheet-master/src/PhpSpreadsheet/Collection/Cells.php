<?php

namespace PhpOffice\PhpSpreadsheet\Collection;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\SimpleCache\CacheInterface;

class Cells
{
    protected const MAX_COLUMN_ID = 16384;

    private CacheInterface $cache;

    
    private ?Worksheet $parent;

    
    private ?Cell $currentCell = null;

    
    private ?string $currentCoordinate = null;

    
    private bool $currentCellIsDirty = false;

    
    private array $index = [];

    
    private string $cachePrefix;

    
    public function __construct(Worksheet $parent, CacheInterface $cache)
    {
        
        
        
        $this->parent = $parent;
        $this->cache = $cache;
        $this->cachePrefix = $this->getUniqueID();
    }

    
    public function getParent(): ?Worksheet
    {
        return $this->parent;
    }

    
    public function has(string $cellCoordinate): bool
    {
        return ($cellCoordinate === $this->currentCoordinate) || isset($this->index[$cellCoordinate]);
    }

    public function has2(string $cellCoordinate): bool
    {
        return isset($this->index[$cellCoordinate]);
    }

    
    public function update(Cell $cell): Cell
    {
        return $this->add($cell->getCoordinate(), $cell);
    }

    
    public function delete(string $cellCoordinate): void
    {
        if ($cellCoordinate === $this->currentCoordinate && $this->currentCell !== null) {
            $this->currentCell->detach();
            $this->currentCoordinate = null;
            $this->currentCell = null;
            $this->currentCellIsDirty = false;
        }

        unset($this->index[$cellCoordinate]);

        
        $this->cache->delete($this->cachePrefix . $cellCoordinate);
    }

    
    public function getCoordinates(): array
    {
        return array_keys($this->index);
    }

    
    public function getSortedCoordinates(): array
    {
        asort($this->index);

        return array_keys($this->index);
    }

    
    public function getSortedCoordinatesInt(): array
    {
        asort($this->index);

        return array_values($this->index);
    }

    
    public function getCurrentCoordinate(): ?string
    {
        return $this->currentCoordinate;
    }

    
    public function getCurrentColumn(): string
    {
        $column = 0;
        $row = '';
        sscanf($this->currentCoordinate ?? '', '%[A-Z]%d', $column, $row);

        return (string) $column;
    }

    
    public function getCurrentRow(): int
    {
        $column = 0;
        $row = '';
        sscanf($this->currentCoordinate ?? '', '%[A-Z]%d', $column, $row);

        return (int) $row;
    }

    
    public function getHighestRowAndColumn(): array
    {
        
        $maxRow = $maxColumn = 1;
        foreach ($this->index as $coordinate) {
            $row = (int) floor(($coordinate - 1) / self::MAX_COLUMN_ID) + 1;
            $maxRow = ($maxRow > $row) ? $maxRow : $row;
            $column = ($coordinate % self::MAX_COLUMN_ID) ?: self::MAX_COLUMN_ID;
            $maxColumn = ($maxColumn > $column) ? $maxColumn : $column;
        }

        return [
            'row' => $maxRow,
            'column' => Coordinate::stringFromColumnIndex($maxColumn),
        ];
    }

    
    public function getHighestColumn($row = null): string
    {
        if ($row === null) {
            return $this->getHighestRowAndColumn()['column'];
        }

        $row = (int) $row;
        if ($row <= 0) {
            throw new PhpSpreadsheetException('Row number must be a positive integer');
        }

        $maxColumn = 1;
        $toRow = $row * self::MAX_COLUMN_ID;
        $fromRow = --$row * self::MAX_COLUMN_ID;
        foreach ($this->index as $coordinate) {
            if ($coordinate < $fromRow || $coordinate >= $toRow) {
                continue;
            }
            $column = ($coordinate % self::MAX_COLUMN_ID) ?: self::MAX_COLUMN_ID;
            $maxColumn = $maxColumn > $column ? $maxColumn : $column;
        }

        return Coordinate::stringFromColumnIndex($maxColumn);
    }

    
    public function getHighestRow(?string $column = null): int
    {
        if ($column === null) {
            return $this->getHighestRowAndColumn()['row'];
        }

        $maxRow = 1;
        $columnIndex = Coordinate::columnIndexFromString($column);
        foreach ($this->index as $coordinate) {
            if ($coordinate % self::MAX_COLUMN_ID !== $columnIndex) {
                continue;
            }
            $row = (int) floor($coordinate / self::MAX_COLUMN_ID) + 1;
            $maxRow = ($maxRow > $row) ? $maxRow : $row;
        }

        return $maxRow;
    }

    
    private function getUniqueID(): string
    {
        $cacheType = Settings::getCache();

        return ($cacheType instanceof Memory\SimpleCache1 || $cacheType instanceof Memory\SimpleCache3)
            ? random_bytes(7) . ':'
            : uniqid('phpspreadsheet.', true) . '.';
    }

    
    public function cloneCellCollection(Worksheet $worksheet): static
    {
        $this->storeCurrentCell();
        $newCollection = clone $this;

        $newCollection->parent = $worksheet;
        $newCollection->cachePrefix = $newCollection->getUniqueID();

        foreach ($this->index as $key => $value) {
            $newCollection->index[$key] = $value;
            $stored = $newCollection->cache->set(
                $newCollection->cachePrefix . $key,
                clone $this->getCache($key)
            );
            if ($stored === false) {
                $this->destructIfNeeded($newCollection, 'Failed to copy cells in cache');
            }
        }

        return $newCollection;
    }

    
    public function removeRow($row): void
    {
        $this->storeCurrentCell();
        $row = (int) $row;
        if ($row <= 0) {
            throw new PhpSpreadsheetException('Row number must be a positive integer');
        }

        $toRow = $row * self::MAX_COLUMN_ID;
        $fromRow = --$row * self::MAX_COLUMN_ID;
        foreach ($this->index as $coordinate) {
            if ($coordinate >= $fromRow && $coordinate < $toRow) {
                $row = (int) floor($coordinate / self::MAX_COLUMN_ID) + 1;
                $column = Coordinate::stringFromColumnIndex($coordinate % self::MAX_COLUMN_ID);
                $this->delete("{$column}{$row}");
            }
        }
    }

    
    public function removeColumn(string $column): void
    {
        $this->storeCurrentCell();

        $columnIndex = Coordinate::columnIndexFromString($column);
        foreach ($this->index as $coordinate) {
            if ($coordinate % self::MAX_COLUMN_ID === $columnIndex) {
                $row = (int) floor($coordinate / self::MAX_COLUMN_ID) + 1;
                $column = Coordinate::stringFromColumnIndex($coordinate % self::MAX_COLUMN_ID);
                $this->delete("{$column}{$row}");
            }
        }
    }

    
    private function storeCurrentCell(): void
    {
        if ($this->currentCellIsDirty && isset($this->currentCoordinate, $this->currentCell)) {
            $this->currentCell->detach();

            $stored = $this->cache->set($this->cachePrefix . $this->currentCoordinate, $this->currentCell);
            if ($stored === false) {
                $this->destructIfNeeded($this, "Failed to store cell {$this->currentCoordinate} in cache");
            }
            $this->currentCellIsDirty = false;
        }

        $this->currentCoordinate = null;
        $this->currentCell = null;
    }

    private function destructIfNeeded(self $cells, string $message): void
    {
        $cells->__destruct();

        throw new PhpSpreadsheetException($message);
    }

    
    public function add(string $cellCoordinate, Cell $cell): Cell
    {
        if ($cellCoordinate !== $this->currentCoordinate) {
            $this->storeCurrentCell();
        }
        $column = 0;
        $row = '';
        sscanf($cellCoordinate, '%[A-Z]%d', $column, $row);
        $this->index[$cellCoordinate] = (--$row * self::MAX_COLUMN_ID) + Coordinate::columnIndexFromString((string) $column);

        $this->currentCoordinate = $cellCoordinate;
        $this->currentCell = $cell;
        $this->currentCellIsDirty = true;

        return $cell;
    }

    
    public function get(string $cellCoordinate): ?Cell
    {
        if ($cellCoordinate === $this->currentCoordinate) {
            return $this->currentCell;
        }
        $this->storeCurrentCell();

        
        if ($this->has($cellCoordinate) === false) {
            return null;
        }

        $cell = $this->getcache($cellCoordinate);

        
        $this->currentCoordinate = $cellCoordinate;
        $this->currentCell = $cell;
        
        $this->currentCell->attach($this);

        
        return $this->currentCell;
    }

    
    public function unsetWorksheetCells(): void
    {
        if ($this->currentCell !== null) {
            $this->currentCell->detach();
            $this->currentCell = null;
            $this->currentCoordinate = null;
        }

        
        $this->__destruct();

        $this->index = [];

        
        $this->parent = null;
    }

    
    public function __destruct()
    {
        $this->cache->deleteMultiple($this->getAllCacheKeys());
        $this->parent = null;
    }

    
    private function getAllCacheKeys(): iterable
    {
        foreach ($this->index as $coordinate => $value) {
            yield $this->cachePrefix . $coordinate;
        }
    }

    private function getCache(string $cellCoordinate): Cell
    {
        $cell = $this->cache->get($this->cachePrefix . $cellCoordinate);
        if (!($cell instanceof Cell)) {
            throw new PhpSpreadsheetException("Cell entry {$cellCoordinate} no longer exists in cache. This probably means that the cache was cleared by someone else.");
        }

        return $cell;
    }
}
