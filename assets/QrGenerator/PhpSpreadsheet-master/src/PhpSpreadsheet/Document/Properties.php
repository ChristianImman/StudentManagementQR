<?php

namespace PhpOffice\PhpSpreadsheet\Document;

use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\IntOrFloat;

class Properties
{
    
    public const PROPERTY_TYPE_BOOLEAN = 'b';
    public const PROPERTY_TYPE_INTEGER = 'i';
    public const PROPERTY_TYPE_FLOAT = 'f';
    public const PROPERTY_TYPE_DATE = 'd';
    public const PROPERTY_TYPE_STRING = 's';
    public const PROPERTY_TYPE_UNKNOWN = 'u';

    private const VALID_PROPERTY_TYPE_LIST = [
        self::PROPERTY_TYPE_BOOLEAN,
        self::PROPERTY_TYPE_INTEGER,
        self::PROPERTY_TYPE_FLOAT,
        self::PROPERTY_TYPE_DATE,
        self::PROPERTY_TYPE_STRING,
    ];

    
    private string $creator = 'Unknown Creator';

    
    private string $lastModifiedBy;

    
    private float|int $created;

    
    private float|int $modified;

    
    private string $title = 'Untitled Spreadsheet';

    
    private string $description = '';

    
    private string $subject = '';

    
    private string $keywords = '';

    
    private string $category = '';

    
    private string $manager = '';

    
    private string $company = '';

    
    private array $customProperties = [];

    private string $hyperlinkBase = '';

    private string $viewport = '';

    
    public function __construct()
    {
        
        $this->lastModifiedBy = $this->creator;
        $this->created = self::intOrFloatTimestamp(null);
        $this->modified = $this->created;
    }

    
    public function getCreator(): string
    {
        return $this->creator;
    }

    
    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    
    public function getLastModifiedBy(): string
    {
        return $this->lastModifiedBy;
    }

    
    public function setLastModifiedBy(string $modifiedBy): self
    {
        $this->lastModifiedBy = $modifiedBy;

        return $this;
    }

    private static function intOrFloatTimestamp(null|bool|float|int|string $timestamp): float|int
    {
        if ($timestamp === null || is_bool($timestamp)) {
            $timestamp = (float) (new DateTime())->format('U');
        } elseif (is_string($timestamp)) {
            if (is_numeric($timestamp)) {
                $timestamp = (float) $timestamp;
            } else {
                $timestamp = (string) preg_replace('/[.][0-9]*$/', '', $timestamp);
                $timestamp = (string) preg_replace('/^(\d{4})- (\d)/', '$1-0$2', $timestamp);
                $timestamp = (string) preg_replace('/^(\d{4}-\d{2})- (\d)/', '$1-0$2', $timestamp);
                $timestamp = (float) (new DateTime($timestamp))->format('U');
            }
        }

        return IntOrFloat::evaluate($timestamp);
    }

    
    public function getCreated(): float|int
    {
        return $this->created;
    }

    
    public function setCreated(null|float|int|string $timestamp): self
    {
        $this->created = self::intOrFloatTimestamp($timestamp);

        return $this;
    }

    
    public function getModified(): float|int
    {
        return $this->modified;
    }

    
    public function setModified(null|float|int|string $timestamp): self
    {
        $this->modified = self::intOrFloatTimestamp($timestamp);

        return $this;
    }

    
    public function getTitle(): string
    {
        return $this->title;
    }

    
    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    
    public function getSubject(): string
    {
        return $this->subject;
    }

    
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    
    public function setKeywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    
    public function getCategory(): string
    {
        return $this->category;
    }

    
    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    
    public function getCompany(): string
    {
        return $this->company;
    }

    
    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    
    public function getManager(): string
    {
        return $this->manager;
    }

    
    public function setManager(string $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    
    public function getCustomProperties(): array
    {
        return array_keys($this->customProperties);
    }

    
    public function isCustomPropertySet(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->customProperties);
    }

    
    public function getCustomPropertyValue(string $propertyName): bool|int|float|string|null
    {
        if (isset($this->customProperties[$propertyName])) {
            return $this->customProperties[$propertyName]['value'];
        }

        return null;
    }

    
    public function getCustomPropertyType(string $propertyName): ?string
    {
        return $this->customProperties[$propertyName]['type'] ?? null;
    }

    private function identifyPropertyType(bool|int|float|string|null $propertyValue): string
    {
        if (is_float($propertyValue)) {
            return self::PROPERTY_TYPE_FLOAT;
        }
        if (is_int($propertyValue)) {
            return self::PROPERTY_TYPE_INTEGER;
        }
        if (is_bool($propertyValue)) {
            return self::PROPERTY_TYPE_BOOLEAN;
        }

        return self::PROPERTY_TYPE_STRING;
    }

    
    public function setCustomProperty(string $propertyName, bool|int|float|string|null $propertyValue = '', ?string $propertyType = null): self
    {
        if (($propertyType === null) || (!in_array($propertyType, self::VALID_PROPERTY_TYPE_LIST))) {
            $propertyType = $this->identifyPropertyType($propertyValue);
        }

        $this->customProperties[$propertyName] = [
            'value' => self::convertProperty($propertyValue, $propertyType),
            'type' => $propertyType,
        ];

        return $this;
    }

    private const PROPERTY_TYPE_ARRAY = [
        'i' => self::PROPERTY_TYPE_INTEGER,      
        'i1' => self::PROPERTY_TYPE_INTEGER,     
        'i2' => self::PROPERTY_TYPE_INTEGER,     
        'i4' => self::PROPERTY_TYPE_INTEGER,     
        'i8' => self::PROPERTY_TYPE_INTEGER,     
        'int' => self::PROPERTY_TYPE_INTEGER,    
        'ui1' => self::PROPERTY_TYPE_INTEGER,    
        'ui2' => self::PROPERTY_TYPE_INTEGER,    
        'ui4' => self::PROPERTY_TYPE_INTEGER,    
        'ui8' => self::PROPERTY_TYPE_INTEGER,    
        'uint' => self::PROPERTY_TYPE_INTEGER,   
        'f' => self::PROPERTY_TYPE_FLOAT,        
        'r4' => self::PROPERTY_TYPE_FLOAT,       
        'r8' => self::PROPERTY_TYPE_FLOAT,       
        'decimal' => self::PROPERTY_TYPE_FLOAT,  
        's' => self::PROPERTY_TYPE_STRING,       
        'empty' => self::PROPERTY_TYPE_STRING,   
        'null' => self::PROPERTY_TYPE_STRING,    
        'lpstr' => self::PROPERTY_TYPE_STRING,   
        'lpwstr' => self::PROPERTY_TYPE_STRING,  
        'bstr' => self::PROPERTY_TYPE_STRING,    
        'd' => self::PROPERTY_TYPE_DATE,         
        'date' => self::PROPERTY_TYPE_DATE,      
        'filetime' => self::PROPERTY_TYPE_DATE,  
        'b' => self::PROPERTY_TYPE_BOOLEAN,      
        'bool' => self::PROPERTY_TYPE_BOOLEAN,   
    ];

    private const SPECIAL_TYPES = [
        'empty' => '',
        'null' => null,
    ];

    
    public static function convertProperty(bool|int|float|string|null $propertyValue, string $propertyType): bool|int|float|string|null
    {
        return self::SPECIAL_TYPES[$propertyType] ?? self::convertProperty2($propertyValue, $propertyType);
    }

    
    private static function convertProperty2(bool|int|float|string|null $propertyValue, string $type): bool|int|float|string|null
    {
        $propertyType = self::convertPropertyType($type);
        switch ($propertyType) {
            case self::PROPERTY_TYPE_INTEGER:
                $intValue = (int) $propertyValue;

                return ($type[0] === 'u') ? abs($intValue) : $intValue;
            case self::PROPERTY_TYPE_FLOAT:
                return (float) $propertyValue;
            case self::PROPERTY_TYPE_DATE:
                return self::intOrFloatTimestamp($propertyValue);
            case self::PROPERTY_TYPE_BOOLEAN:
                return is_bool($propertyValue) ? $propertyValue : ($propertyValue === 'true');
            default: 
                return $propertyValue;
        }
    }

    public static function convertPropertyType(string $propertyType): string
    {
        return self::PROPERTY_TYPE_ARRAY[$propertyType] ?? self::PROPERTY_TYPE_UNKNOWN;
    }

    public function getHyperlinkBase(): string
    {
        return $this->hyperlinkBase;
    }

    public function setHyperlinkBase(string $hyperlinkBase): self
    {
        $this->hyperlinkBase = $hyperlinkBase;

        return $this;
    }

    public function getViewport(): string
    {
        return $this->viewport;
    }

    public const SUGGESTED_VIEWPORT = 'width=device-width, initial-scale=1';

    public function setViewport(string $viewport): self
    {
        $this->viewport = $viewport;

        return $this;
    }
}
