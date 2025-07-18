<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Csv;

class Delimiter
{
    protected const POTENTIAL_DELIMETERS = [',', ';', "\t", '|', ':', ' ', '~'];

    
    protected $fileHandle;

    protected string $escapeCharacter;

    protected string $enclosure;

    
    protected array $counts = [];

    protected int $numberLines = 0;

    protected ?string $delimiter = null;

    
    public function __construct($fileHandle, string $escapeCharacter, string $enclosure)
    {
        $this->fileHandle = $fileHandle;
        $this->escapeCharacter = $escapeCharacter;
        $this->enclosure = $enclosure;

        $this->countPotentialDelimiters();
    }

    public function getDefaultDelimiter(): string
    {
        return self::POTENTIAL_DELIMETERS[0];
    }

    public function linesCounted(): int
    {
        return $this->numberLines;
    }

    protected function countPotentialDelimiters(): void
    {
        $this->counts = array_fill_keys(self::POTENTIAL_DELIMETERS, []);
        $delimiterKeys = array_flip(self::POTENTIAL_DELIMETERS);

        
        $this->numberLines = 0;
        while (($line = $this->getNextLine()) !== false && (++$this->numberLines < 1000)) {
            $this->countDelimiterValues($line, $delimiterKeys);
        }
    }

    
    protected function countDelimiterValues(string $line, array $delimiterKeys): void
    {
        $splitString = mb_str_split($line, 1, 'UTF-8');
        $distribution = array_count_values($splitString);
        $countLine = array_intersect_key($distribution, $delimiterKeys);

        foreach (self::POTENTIAL_DELIMETERS as $delimiter) {
            $this->counts[$delimiter][] = $countLine[$delimiter] ?? 0;
        }
    }

    public function infer(): ?string
    {
        
        
        $meanSquareDeviations = [];
        $middleIdx = (int) floor(($this->numberLines - 1) / 2);

        foreach (self::POTENTIAL_DELIMETERS as $delimiter) {
            $series = $this->counts[$delimiter];
            sort($series);

            $median = ($this->numberLines % 2)
                ? $series[$middleIdx]
                : ($series[$middleIdx] + $series[$middleIdx + 1]) / 2;

            if ($median === 0) {
                continue;
            }

            $meanSquareDeviations[$delimiter] = array_reduce(
                $series,
                fn ($sum, $value): int|float => $sum + ($value - $median) ** 2
            ) / count($series);
        }

        
        
        $min = INF;
        foreach (self::POTENTIAL_DELIMETERS as $delimiter) {
            if (!isset($meanSquareDeviations[$delimiter])) {
                continue;
            }

            if ($meanSquareDeviations[$delimiter] < $min) {
                $min = $meanSquareDeviations[$delimiter];
                $this->delimiter = $delimiter;
            }
        }

        return $this->delimiter;
    }

    
    public function getNextLine()
    {
        $line = '';
        $enclosure = ($this->escapeCharacter === '' ? ''
                : ('(?<!' . preg_quote($this->escapeCharacter, '/') . ')'))
            . preg_quote($this->enclosure, '/');

        do {
            
            $newLine = fgets($this->fileHandle);

            
            if ($newLine === false) {
                return false;
            }

            
            $line = $line . $newLine;

            
            $line = (string) preg_replace('/(' . $enclosure . '.*' . $enclosure . ')/Us', '', $line);

            
            
        } while (preg_match('/(' . $enclosure . ')/', $line) > 0);

        return ($line !== '') ? $line : false;
    }
}
