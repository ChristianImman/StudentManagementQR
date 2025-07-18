<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\IComparable;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing\Shadow;
use SimpleXMLElement;

class BaseDrawing implements IComparable
{
    const EDIT_AS_ABSOLUTE = 'absolute';
    const EDIT_AS_ONECELL = 'oneCell';
    const EDIT_AS_TWOCELL = 'twoCell';
    private const VALID_EDIT_AS = [
        self::EDIT_AS_ABSOLUTE,
        self::EDIT_AS_ONECELL,
        self::EDIT_AS_TWOCELL,
    ];

    
    protected string $editAs = '';

    
    private static int $imageCounter = 0;

    
    private int $imageIndex;

    
    protected string $name = '';

    
    protected string $description = '';

    
    protected ?Worksheet $worksheet = null;

    
    protected string $coordinates = 'A1';

    
    protected int $offsetX = 0;

    
    protected int $offsetY = 0;

    
    protected string $coordinates2 = '';

    
    protected int $offsetX2 = 0;

    
    protected int $offsetY2 = 0;

    
    protected int $width = 0;

    
    protected int $height = 0;

    
    protected int $imageWidth = 0;

    
    protected int $imageHeight = 0;

    
    protected bool $resizeProportional = true;

    
    protected int $rotation = 0;

    protected bool $flipVertical = false;

    protected bool $flipHorizontal = false;

    
    protected Shadow $shadow;

    
    private ?Hyperlink $hyperlink = null;

    
    protected int $type = IMAGETYPE_UNKNOWN;

    
    protected $srcRect = [];

    
    protected ?int $opacity = null;

    
    public function __construct()
    {
        
        $this->setShadow();

        
        ++self::$imageCounter;
        $this->imageIndex = self::$imageCounter;
    }

    public function __destruct()
    {
        $this->worksheet = null;
    }

    public function getImageIndex(): int
    {
        return $this->imageIndex;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getWorksheet(): ?Worksheet
    {
        return $this->worksheet;
    }

    
    public function setWorksheet(?Worksheet $worksheet = null, bool $overrideOld = false): self
    {
        if ($this->worksheet === null) {
            
            if ($worksheet !== null) {
                $this->worksheet = $worksheet;
                if (!($this instanceof Drawing && $this->getPath() === '')) {
                    $this->worksheet->getCell($this->coordinates);
                }
                $this->worksheet->getDrawingCollection()
                    ->append($this);
            }
        } else {
            if ($overrideOld) {
                
                $iterator = $this->worksheet->getDrawingCollection()->getIterator();

                while ($iterator->valid()) {
                    if ($iterator->current()->getHashCode() === $this->getHashCode()) {
                        $this->worksheet->getDrawingCollection()->offsetUnset($iterator->key());
                        $this->worksheet = null;

                        break;
                    }
                }

                
                $this->setWorksheet($worksheet);
            } else {
                throw new PhpSpreadsheetException('A Worksheet has already been assigned. Drawings can only exist on one Worksheet.');
            }
        }

        return $this;
    }

    public function getCoordinates(): string
    {
        return $this->coordinates;
    }

    public function setCoordinates(string $coordinates): self
    {
        $this->coordinates = $coordinates;
        if ($this->worksheet !== null) {
            if (!($this instanceof Drawing && $this->getPath() === '')) {
                $this->worksheet->getCell($this->coordinates);
            }
        }

        return $this;
    }

    public function getOffsetX(): int
    {
        return $this->offsetX;
    }

    public function setOffsetX(int $offsetX): self
    {
        $this->offsetX = $offsetX;

        return $this;
    }

    public function getOffsetY(): int
    {
        return $this->offsetY;
    }

    public function setOffsetY(int $offsetY): self
    {
        $this->offsetY = $offsetY;

        return $this;
    }

    public function getCoordinates2(): string
    {
        return $this->coordinates2;
    }

    public function setCoordinates2(string $coordinates2): self
    {
        $this->coordinates2 = $coordinates2;

        return $this;
    }

    public function getOffsetX2(): int
    {
        return $this->offsetX2;
    }

    public function setOffsetX2(int $offsetX2): self
    {
        $this->offsetX2 = $offsetX2;

        return $this;
    }

    public function getOffsetY2(): int
    {
        return $this->offsetY2;
    }

    public function setOffsetY2(int $offsetY2): self
    {
        $this->offsetY2 = $offsetY2;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        
        if ($this->resizeProportional && $width != 0) {
            $ratio = $this->height / ($this->width != 0 ? $this->width : 1);
            $this->height = (int) round($ratio * $width);
        }

        
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        
        if ($this->resizeProportional && $height != 0) {
            $ratio = $this->width / ($this->height != 0 ? $this->height : 1);
            $this->width = (int) round($ratio * $height);
        }

        
        $this->height = $height;

        return $this;
    }

    
    public function setWidthAndHeight(int $width, int $height): self
    {
        if ($this->width === 0 || $this->height === 0 || $width === 0 || $height === 0 || !$this->resizeProportional) {
            $this->width = $width;
            $this->height = $height;
        } else {
            $xratio = $width / $this->width;
            $yratio = $height / $this->height;
            if (($xratio * $this->height) < $height) {
                $this->height = (int) ceil($xratio * $this->height);
                $this->width = $width;
            } else {
                $this->width = (int) ceil($yratio * $this->width);
                $this->height = $height;
            }
        }

        return $this;
    }

    public function getResizeProportional(): bool
    {
        return $this->resizeProportional;
    }

    public function setResizeProportional(bool $resizeProportional): self
    {
        $this->resizeProportional = $resizeProportional;

        return $this;
    }

    public function getRotation(): int
    {
        return $this->rotation;
    }

    public function setRotation(int $rotation): self
    {
        $this->rotation = $rotation;

        return $this;
    }

    public function getShadow(): Shadow
    {
        return $this->shadow;
    }

    public function setShadow(?Shadow $shadow = null): self
    {
        $this->shadow = $shadow ?? new Shadow();

        return $this;
    }

    
    public function getHashCode(): string
    {
        return md5(
            $this->name
            . $this->description
            . (($this->worksheet === null) ? '' : (string) $this->worksheet->getHashInt())
            . $this->coordinates
            . $this->offsetX
            . $this->offsetY
            . $this->coordinates2
            . $this->offsetX2
            . $this->offsetY2
            . $this->width
            . $this->height
            . $this->rotation
            . $this->shadow->getHashCode()
            . __CLASS__
        );
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ($key == 'worksheet') {
                $this->worksheet = null;
            } elseif (is_object($value)) {
                $this->$key = clone $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    public function setHyperlink(?Hyperlink $hyperlink = null): void
    {
        $this->hyperlink = $hyperlink;
    }

    public function getHyperlink(): ?Hyperlink
    {
        return $this->hyperlink;
    }

    
    protected function setSizesAndType(string $path): void
    {
        if ($this->imageWidth === 0 && $this->imageHeight === 0 && $this->type === IMAGETYPE_UNKNOWN) {
            $imageData = getimagesize($path);

            if (!empty($imageData)) {
                $this->imageWidth = $imageData[0];
                $this->imageHeight = $imageData[1];
                $this->type = $imageData[2];
            }
        }
        if ($this->width === 0 && $this->height === 0) {
            $this->width = $this->imageWidth;
            $this->height = $this->imageHeight;
        }
    }

    
    public function getType(): int
    {
        return $this->type;
    }

    public function getImageWidth(): int
    {
        return $this->imageWidth;
    }

    public function getImageHeight(): int
    {
        return $this->imageHeight;
    }

    public function getEditAs(): string
    {
        return $this->editAs;
    }

    public function setEditAs(string $editAs): self
    {
        $this->editAs = $editAs;

        return $this;
    }

    public function validEditAs(): bool
    {
        return in_array($this->editAs, self::VALID_EDIT_AS, true);
    }

    
    public function getSrcRect()
    {
        return $this->srcRect;
    }

    
    public function setSrcRect($srcRect): self
    {
        $this->srcRect = $srcRect;

        return $this;
    }

    public function setFlipHorizontal(bool $flipHorizontal): self
    {
        $this->flipHorizontal = $flipHorizontal;

        return $this;
    }

    public function getFlipHorizontal(): bool
    {
        return $this->flipHorizontal;
    }

    public function setFlipVertical(bool $flipVertical): self
    {
        $this->flipVertical = $flipVertical;

        return $this;
    }

    public function getFlipVertical(): bool
    {
        return $this->flipVertical;
    }

    public function setOpacity(?int $opacity): self
    {
        $this->opacity = $opacity;

        return $this;
    }

    public function getOpacity(): ?int
    {
        return $this->opacity;
    }
}
