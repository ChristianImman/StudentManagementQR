<?php

namespace PhpOffice\PhpSpreadsheet\Shared\OLE\PPS;



















use PhpOffice\PhpSpreadsheet\Shared\OLE;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS;


class Root extends PPS
{
    
    private $fileHandle;

    private ?int $smallBlockSize = null;

    private ?int $bigBlockSize = null;

    
    public function __construct($time_1st, $time_2nd, array $raChild)
    {
        parent::__construct(null, OLE::ascToUcs('Root Entry'), OLE::OLE_PPS_TYPE_ROOT, null, null, null, $time_1st, $time_2nd, null, $raChild);
    }

    
    public function save($fileHandle): bool
    {
        $this->fileHandle = $fileHandle;

        
        $this->bigBlockSize = (int) (2 ** (
            (isset($this->bigBlockSize)) ? self::adjust2($this->bigBlockSize) : 9
        ));
        $this->smallBlockSize = (int) (2 ** (
            (isset($this->smallBlockSize)) ? self::adjust2($this->smallBlockSize) : 6
        ));

        
        $aList = [];
        PPS::savePpsSetPnt($aList, [$this]);
        
        [$iSBDcnt, $iBBcnt, $iPPScnt] = $this->calcSize($aList); 
        
        $this->saveHeader((int) $iSBDcnt, (int) $iBBcnt, (int) $iPPScnt);

        
        $this->_data = $this->makeSmallData($aList);

        
        $this->saveBigData((int) $iSBDcnt, $aList);
        
        $this->savePps($aList);
        
        $this->saveBbd((int) $iSBDcnt, (int) $iBBcnt, (int) $iPPScnt);

        return true;
    }

    
    private function calcSize(array &$raList): array
    {
        
        [$iSBDcnt, $iBBcnt, $iPPScnt] = [0, 0, 0];
        $iSBcnt = 0;
        $iCount = count($raList);
        for ($i = 0; $i < $iCount; ++$i) {
            if ($raList[$i]->Type == OLE::OLE_PPS_TYPE_FILE) {
                $raList[$i]->Size = $raList[$i]->getDataLen();
                if ($raList[$i]->Size < OLE::OLE_DATA_SIZE_SMALL) {
                    $iSBcnt += floor($raList[$i]->Size / $this->smallBlockSize)
                        + (($raList[$i]->Size % $this->smallBlockSize) ? 1 : 0);
                } else {
                    $iBBcnt += (floor($raList[$i]->Size / $this->bigBlockSize)
                        + (($raList[$i]->Size % $this->bigBlockSize) ? 1 : 0));
                }
            }
        }
        $iSmallLen = $iSBcnt * $this->smallBlockSize;
        $iSlCnt = floor($this->bigBlockSize / OLE::OLE_LONG_INT_SIZE);
        $iSBDcnt = floor($iSBcnt / $iSlCnt) + (($iSBcnt % $iSlCnt) ? 1 : 0);
        $iBBcnt += (floor($iSmallLen / $this->bigBlockSize)
            + (($iSmallLen % $this->bigBlockSize) ? 1 : 0));
        $iCnt = count($raList);
        $iBdCnt = $this->bigBlockSize / OLE::OLE_PPS_SIZE;
        $iPPScnt = (floor($iCnt / $iBdCnt) + (($iCnt % $iBdCnt) ? 1 : 0));

        return [$iSBDcnt, $iBBcnt, $iPPScnt];
    }

    
    private static function adjust2(int $i2): float
    {
        $iWk = log($i2) / log(2);

        return ($iWk > floor($iWk)) ? floor($iWk) + 1 : $iWk;
    }

    
    private function saveHeader(int $iSBDcnt, int $iBBcnt, int $iPPScnt): void
    {
        $FILE = $this->fileHandle;

        
        $iBlCnt = $this->bigBlockSize / OLE::OLE_LONG_INT_SIZE;
        $i1stBdL = ($this->bigBlockSize - 0x4C) / OLE::OLE_LONG_INT_SIZE;

        $iBdExL = 0;
        $iAll = $iBBcnt + $iPPScnt + $iSBDcnt;
        $iAllW = $iAll;
        $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt) ? 1 : 0);
        $iBdCnt = floor(($iAll + $iBdCntW) / $iBlCnt) + ((($iAllW + $iBdCntW) % $iBlCnt) ? 1 : 0);

        
        if ($iBdCnt > $i1stBdL) {
            while (1) {
                ++$iBdExL;
                ++$iAllW;
                $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt) ? 1 : 0);
                $iBdCnt = floor(($iAllW + $iBdCntW) / $iBlCnt) + ((($iAllW + $iBdCntW) % $iBlCnt) ? 1 : 0);
                if ($iBdCnt <= ($iBdExL * $iBlCnt + $i1stBdL)) {
                    break;
                }
            }
        }

        
        fwrite(
            $FILE,
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
            . "\x00\x00\x00\x00"
            . "\x00\x00\x00\x00"
            . "\x00\x00\x00\x00"
            . "\x00\x00\x00\x00"
            . pack('v', 0x3B)
            . pack('v', 0x03)
            . pack('v', -2)
            . pack('v', 9)
            . pack('v', 6)
            . pack('v', 0)
            . "\x00\x00\x00\x00"
            . "\x00\x00\x00\x00"
            . pack('V', $iBdCnt)
            . pack('V', $iBBcnt + $iSBDcnt) 
            . pack('V', 0)
            . pack('V', 0x1000)
            . pack('V', $iSBDcnt ? 0 : -2) 
            . pack('V', $iSBDcnt)
        );
        
        if ($iBdCnt < $i1stBdL) {
            fwrite(
                $FILE,
                pack('V', -2) 
                . pack('V', 0)
            );
        } else {
            fwrite($FILE, pack('V', $iAll + $iBdCnt) . pack('V', $iBdExL));
        }

        
        for ($i = 0; $i < $i1stBdL && $i < $iBdCnt; ++$i) {
            fwrite($FILE, pack('V', $iAll + $i));
        }
        if ($i < $i1stBdL) {
            $jB = $i1stBdL - $i;
            for ($j = 0; $j < $jB; ++$j) {
                fwrite($FILE, (pack('V', -1)));
            }
        }
    }

    
    private function saveBigData(int $iStBlk, array &$raList): void
    {
        $FILE = $this->fileHandle;

        
        $iCount = count($raList);
        for ($i = 0; $i < $iCount; ++$i) {
            if ($raList[$i]->Type != OLE::OLE_PPS_TYPE_DIR) {
                $raList[$i]->Size = $raList[$i]->getDataLen();
                if (($raList[$i]->Size >= OLE::OLE_DATA_SIZE_SMALL) || (($raList[$i]->Type == OLE::OLE_PPS_TYPE_ROOT) && isset($raList[$i]->_data))) {
                    fwrite($FILE, $raList[$i]->_data);

                    if ($raList[$i]->Size % $this->bigBlockSize) {
                        fwrite($FILE, str_repeat("\x00", $this->bigBlockSize - ($raList[$i]->Size % $this->bigBlockSize)));
                    }
                    
                    $raList[$i]->startBlock = $iStBlk;
                    $iStBlk
                        += (floor($raList[$i]->Size / $this->bigBlockSize)
                            + (($raList[$i]->Size % $this->bigBlockSize) ? 1 : 0));
                }
            }
        }
    }

    
    private function makeSmallData(array &$raList): string
    {
        $sRes = '';
        $FILE = $this->fileHandle;
        $iSmBlk = 0;

        $iCount = count($raList);
        for ($i = 0; $i < $iCount; ++$i) {
            
            if ($raList[$i]->Type == OLE::OLE_PPS_TYPE_FILE) {
                if ($raList[$i]->Size <= 0) {
                    continue;
                }
                if ($raList[$i]->Size < OLE::OLE_DATA_SIZE_SMALL) {
                    $iSmbCnt = floor($raList[$i]->Size / $this->smallBlockSize)
                        + (($raList[$i]->Size % $this->smallBlockSize) ? 1 : 0);
                    
                    $jB = $iSmbCnt - 1;
                    for ($j = 0; $j < $jB; ++$j) {
                        fwrite($FILE, pack('V', $j + $iSmBlk + 1));
                    }
                    fwrite($FILE, pack('V', -2));

                    
                    $sRes .= $raList[$i]->_data;
                    if ($raList[$i]->Size % $this->smallBlockSize) {
                        $sRes .= str_repeat("\x00", $this->smallBlockSize - ($raList[$i]->Size % $this->smallBlockSize));
                    }
                    
                    $raList[$i]->startBlock = $iSmBlk;
                    $iSmBlk += $iSmbCnt;
                }
            }
        }
        $iSbCnt = floor($this->bigBlockSize / OLE::OLE_LONG_INT_SIZE);
        if ($iSmBlk % $iSbCnt) {
            $iB = $iSbCnt - ($iSmBlk % $iSbCnt);
            for ($i = 0; $i < $iB; ++$i) {
                fwrite($FILE, pack('V', -1));
            }
        }

        return $sRes;
    }

    
    private function savePps(array &$raList): void
    {
        
        $iC = count($raList);
        for ($i = 0; $i < $iC; ++$i) {
            fwrite($this->fileHandle, $raList[$i]->getPpsWk());
        }
        
        $iCnt = count($raList);
        $iBCnt = $this->bigBlockSize / OLE::OLE_PPS_SIZE;
        if ($iCnt % $iBCnt) {
            fwrite($this->fileHandle, str_repeat("\x00", ($iBCnt - ($iCnt % $iBCnt)) * OLE::OLE_PPS_SIZE));
        }
    }

    
    private function saveBbd(int $iSbdSize, int $iBsize, int $iPpsCnt): void
    {
        $FILE = $this->fileHandle;
        
        $iBbCnt = $this->bigBlockSize / OLE::OLE_LONG_INT_SIZE;
        $i1stBdL = ($this->bigBlockSize - 0x4C) / OLE::OLE_LONG_INT_SIZE;

        $iBdExL = 0;
        $iAll = $iBsize + $iPpsCnt + $iSbdSize;
        $iAllW = $iAll;
        $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt) ? 1 : 0);
        $iBdCnt = floor(($iAll + $iBdCntW) / $iBbCnt) + ((($iAllW + $iBdCntW) % $iBbCnt) ? 1 : 0);
        
        if ($iBdCnt > $i1stBdL) {
            while (1) {
                ++$iBdExL;
                ++$iAllW;
                $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt) ? 1 : 0);
                $iBdCnt = floor(($iAllW + $iBdCntW) / $iBbCnt) + ((($iAllW + $iBdCntW) % $iBbCnt) ? 1 : 0);
                if ($iBdCnt <= ($iBdExL * $iBbCnt + $i1stBdL)) {
                    break;
                }
            }
        }

        
        
        if ($iSbdSize > 0) {
            for ($i = 0; $i < ($iSbdSize - 1); ++$i) {
                fwrite($FILE, pack('V', $i + 1));
            }
            fwrite($FILE, pack('V', -2));
        }
        
        for ($i = 0; $i < ($iBsize - 1); ++$i) {
            fwrite($FILE, pack('V', $i + $iSbdSize + 1));
        }
        fwrite($FILE, pack('V', -2));

        
        for ($i = 0; $i < ($iPpsCnt - 1); ++$i) {
            fwrite($FILE, pack('V', $i + $iSbdSize + $iBsize + 1));
        }
        fwrite($FILE, pack('V', -2));
        
        for ($i = 0; $i < $iBdCnt; ++$i) {
            fwrite($FILE, pack('V', 0xFFFFFFFD));
        }
        
        for ($i = 0; $i < $iBdExL; ++$i) {
            fwrite($FILE, pack('V', 0xFFFFFFFC));
        }
        
        if (($iAllW + $iBdCnt) % $iBbCnt) {
            $iBlock = ($iBbCnt - (($iAllW + $iBdCnt) % $iBbCnt));
            for ($i = 0; $i < $iBlock; ++$i) {
                fwrite($FILE, pack('V', -1));
            }
        }
        
        if ($iBdCnt > $i1stBdL) {
            $iN = 0;
            $iNb = 0;
            for ($i = $i1stBdL; $i < $iBdCnt; $i++, ++$iN) {
                if ($iN >= ($iBbCnt - 1)) {
                    $iN = 0;
                    ++$iNb;
                    fwrite($FILE, pack('V', $iAll + $iBdCnt + $iNb));
                }
                fwrite($FILE, pack('V', $iBsize + $iSbdSize + $iPpsCnt + $i));
            }
            if (($iBdCnt - $i1stBdL) % ($iBbCnt - 1)) {
                $iB = ($iBbCnt - 1) - (($iBdCnt - $i1stBdL) % ($iBbCnt - 1));
                for ($i = 0; $i < $iB; ++$i) {
                    fwrite($FILE, pack('V', -1));
                }
            }
            fwrite($FILE, pack('V', -2));
        }
    }
}
