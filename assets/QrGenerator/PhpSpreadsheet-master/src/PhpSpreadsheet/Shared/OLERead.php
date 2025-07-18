<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class OLERead
{
    private string $data = '';

    
    const BIG_BLOCK_SIZE = 0x200;

    
    const SMALL_BLOCK_SIZE = 0x40;

    
    const PROPERTY_STORAGE_BLOCK_SIZE = 0x80;

    
    const SMALL_BLOCK_THRESHOLD = 0x1000;

    
    const NUM_BIG_BLOCK_DEPOT_BLOCKS_POS = 0x2C;
    const ROOT_START_BLOCK_POS = 0x30;
    const SMALL_BLOCK_DEPOT_BLOCK_POS = 0x3C;
    const EXTENSION_BLOCK_POS = 0x44;
    const NUM_EXTENSION_BLOCK_POS = 0x48;
    const BIG_BLOCK_DEPOT_BLOCKS_POS = 0x4C;

    
    const SIZE_OF_NAME_POS = 0x40;
    const TYPE_POS = 0x42;
    const START_BLOCK_POS = 0x74;
    const SIZE_POS = 0x78;

    public ?int $wrkbook = null;

    public ?int $summaryInformation = null;

    public ?int $documentSummaryInformation = null;

    private int $numBigBlockDepotBlocks;

    private int $rootStartBlock;

    private int $sbdStartBlock;

    private int $extensionBlock;

    private int $numExtensionBlocks;

    private string $bigBlockChain;

    private string $smallBlockChain;

    private string $entry;

    private int $rootentry;

    private array $props = [];

    
    public function read(string $filename): void
    {
        File::assertFile($filename);

        
        
        $this->data = (string) file_get_contents($filename, false, null, 0, 8);

        
        $identifierOle = pack('CCCCCCCC', 0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1);
        if ($this->data != $identifierOle) {
            throw new ReaderException('The filename ' . $filename . ' is not recognised as an OLE file');
        }

        
        $this->data = (string) file_get_contents($filename);

        
        $this->numBigBlockDepotBlocks = self::getInt4d($this->data, self::NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);

        
        $this->rootStartBlock = self::getInt4d($this->data, self::ROOT_START_BLOCK_POS);

        
        $this->sbdStartBlock = self::getInt4d($this->data, self::SMALL_BLOCK_DEPOT_BLOCK_POS);

        
        $this->extensionBlock = self::getInt4d($this->data, self::EXTENSION_BLOCK_POS);

        
        $this->numExtensionBlocks = self::getInt4d($this->data, self::NUM_EXTENSION_BLOCK_POS);

        $bigBlockDepotBlocks = [];
        $pos = self::BIG_BLOCK_DEPOT_BLOCKS_POS;

        $bbdBlocks = $this->numBigBlockDepotBlocks;

        if ($this->numExtensionBlocks !== 0) {
            $bbdBlocks = (self::BIG_BLOCK_SIZE - self::BIG_BLOCK_DEPOT_BLOCKS_POS) / 4;
        }

        for ($i = 0; $i < $bbdBlocks; ++$i) {
            $bigBlockDepotBlocks[$i] = self::getInt4d($this->data, $pos);
            $pos += 4;
        }

        for ($j = 0; $j < $this->numExtensionBlocks; ++$j) {
            $pos = ($this->extensionBlock + 1) * self::BIG_BLOCK_SIZE;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, self::BIG_BLOCK_SIZE / 4 - 1);

            for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; ++$i) {
                $bigBlockDepotBlocks[$i] = self::getInt4d($this->data, $pos);
                $pos += 4;
            }

            $bbdBlocks += $blocksToRead;
            if ($bbdBlocks < $this->numBigBlockDepotBlocks) {
                $this->extensionBlock = self::getInt4d($this->data, $pos);
            }
        }

        $pos = 0;
        $this->bigBlockChain = '';
        $bbs = self::BIG_BLOCK_SIZE / 4;
        for ($i = 0; $i < $this->numBigBlockDepotBlocks; ++$i) {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * self::BIG_BLOCK_SIZE;

            $this->bigBlockChain .= substr($this->data, $pos, 4 * $bbs);
            $pos += 4 * $bbs;
        }

        $sbdBlock = $this->sbdStartBlock;
        $this->smallBlockChain = '';
        while ($sbdBlock != -2) {
            $pos = ($sbdBlock + 1) * self::BIG_BLOCK_SIZE;

            $this->smallBlockChain .= substr($this->data, $pos, 4 * $bbs);
            $pos += 4 * $bbs;

            $sbdBlock = self::getInt4d($this->bigBlockChain, $sbdBlock * 4);
        }

        
        $block = $this->rootStartBlock;
        $this->entry = $this->readData($block);

        $this->readPropertySets();
    }

    
    public function getStream(?int $stream): ?string
    {
        if ($stream === null) {
            return null;
        }

        $streamData = '';

        if ($this->props[$stream]['size'] < self::SMALL_BLOCK_THRESHOLD) {
            $rootdata = $this->readData($this->props[$this->rootentry]['startBlock']);

            $block = $this->props[$stream]['startBlock'];

            while ($block != -2) {
                $pos = $block * self::SMALL_BLOCK_SIZE;
                $streamData .= substr($rootdata, $pos, self::SMALL_BLOCK_SIZE);

                $block = self::getInt4d($this->smallBlockChain, $block * 4);
            }

            return $streamData;
        }
        $numBlocks = $this->props[$stream]['size'] / self::BIG_BLOCK_SIZE;
        if ($this->props[$stream]['size'] % self::BIG_BLOCK_SIZE != 0) {
            ++$numBlocks;
        }

        if ($numBlocks == 0) {
            return '';
        }

        $block = $this->props[$stream]['startBlock'];

        while ($block != -2) {
            $pos = ($block + 1) * self::BIG_BLOCK_SIZE;
            $streamData .= substr($this->data, $pos, self::BIG_BLOCK_SIZE);
            $block = self::getInt4d($this->bigBlockChain, $block * 4);
        }

        return $streamData;
    }

    
    private function readData(int $block): string
    {
        $data = '';

        while ($block != -2) {
            $pos = ($block + 1) * self::BIG_BLOCK_SIZE;
            $data .= substr($this->data, $pos, self::BIG_BLOCK_SIZE);
            $block = self::getInt4d($this->bigBlockChain, $block * 4);
        }

        return $data;
    }

    
    private function readPropertySets(): void
    {
        $offset = 0;

        
        $entryLen = strlen($this->entry);
        while ($offset < $entryLen) {
            
            $d = substr($this->entry, $offset, self::PROPERTY_STORAGE_BLOCK_SIZE);

            
            $nameSize = ord($d[self::SIZE_OF_NAME_POS]) | (ord($d[self::SIZE_OF_NAME_POS + 1]) << 8);

            
            $type = ord($d[self::TYPE_POS]);

            
            
            $startBlock = self::getInt4d($d, self::START_BLOCK_POS);

            $size = self::getInt4d($d, self::SIZE_POS);

            $name = str_replace("\x00", '', substr($d, 0, $nameSize));

            $this->props[] = [
                'name' => $name,
                'type' => $type,
                'startBlock' => $startBlock,
                'size' => $size,
            ];

            
            $upName = strtoupper($name);

            
            if (($upName === 'WORKBOOK') || ($upName === 'BOOK')) {
                $this->wrkbook = count($this->props) - 1;
            } elseif ($upName === 'ROOT ENTRY' || $upName === 'R') {
                
                $this->rootentry = count($this->props) - 1;
            }

            
            if ($name == chr(5) . 'SummaryInformation') {
                $this->summaryInformation = count($this->props) - 1;
            }

            
            if ($name == chr(5) . 'DocumentSummaryInformation') {
                $this->documentSummaryInformation = count($this->props) - 1;
            }

            $offset += self::PROPERTY_STORAGE_BLOCK_SIZE;
        }
    }

    
    private static function getInt4d(string $data, int $pos): int
    {
        if ($pos < 0) {
            
            throw new ReaderException('Parameter pos=' . $pos . ' is invalid.');
        }

        $len = strlen($data);
        if ($len < $pos + 4) {
            $data .= str_repeat("\0", $pos + 4 - $len);
        }

        
        
        
        $_or_24 = ord($data[$pos + 3]);
        if ($_or_24 >= 128) {
            
            $_ord_24 = -abs((256 - $_or_24) << 24);
        } else {
            $_ord_24 = ($_or_24 & 127) << 24;
        }

        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | $_ord_24;
    }
}
