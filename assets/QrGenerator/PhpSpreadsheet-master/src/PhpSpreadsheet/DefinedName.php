<?php

namespace PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class DefinedName
{
    protected const REGEXP_IDENTIFY_FORMULA = '[^_\p{N}\p{L}:, \$\'!]';

    
    protected string $name;

    
    protected ?Worksheet $worksheet;

    
    protected string $value;

    
    protected bool $localOnly;

    
    protected ?Worksheet $scope;

    
    protected bool $isFormula;

    
    public function __construct(
        string $name,
        ?Worksheet $worksheet = null,
        ?string $value = null,
        bool $localOnly = false,
        ?Worksheet $scope = null
    ) {
        if ($worksheet === null) {
            $worksheet = $scope;
        }

        
        $this->name = $name;
        $this->worksheet = $worksheet;
        $this->value = (string) $value;
        $this->localOnly = $localOnly;
        
        $this->scope = ($localOnly === true) ? (($scope === null) ? $worksheet : $scope) : null;
        
        
        
        
        $this->isFormula = self::testIfFormula($this->value);
    }

    public function __destruct()
    {
        $this->worksheet = null;
        $this->scope = null;
    }

    
    public static function createInstance(
        string $name,
        ?Worksheet $worksheet = null,
        ?string $value = null,
        bool $localOnly = false,
        ?Worksheet $scope = null
    ): self {
        $value = (string) $value;
        $isFormula = self::testIfFormula($value);
        if ($isFormula) {
            return new NamedFormula($name, $worksheet, $value, $localOnly, $scope);
        }

        return new NamedRange($name, $worksheet, $value, $localOnly, $scope);
    }

    public static function testIfFormula(string $value): bool
    {
        if (str_starts_with($value, '=')) {
            $value = substr($value, 1);
        }

        if (is_numeric($value)) {
            return true;
        }

        $segMatcher = false;
        foreach (explode("'", $value) as $subVal) {
            
            $segMatcher = $segMatcher === false;
            if (
                $segMatcher
                && (preg_match('/' . self::REGEXP_IDENTIFY_FORMULA . '/miu', $subVal))
            ) {
                return true;
            }
        }

        return false;
    }

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function setName(string $name): self
    {
        if (!empty($name)) {
            
            $oldTitle = $this->name;

            
            if ($this->worksheet !== null) {
                $this->worksheet->getParentOrThrow()->removeNamedRange($this->name, $this->worksheet);
            }
            $this->name = $name;

            if ($this->worksheet !== null) {
                $this->worksheet->getParentOrThrow()->addDefinedName($this);
            }

            if ($this->worksheet !== null) {
                
                $newTitle = $this->name;
                ReferenceHelper::getInstance()->updateNamedFormulae($this->worksheet->getParentOrThrow(), $oldTitle, $newTitle);
            }
        }

        return $this;
    }

    
    public function getWorksheet(): ?Worksheet
    {
        return $this->worksheet;
    }

    
    public function setWorksheet(?Worksheet $worksheet): self
    {
        $this->worksheet = $worksheet;

        return $this;
    }

    
    public function getValue(): string
    {
        return $this->value;
    }

    
    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    
    public function getLocalOnly(): bool
    {
        return $this->localOnly;
    }

    
    public function setLocalOnly(bool $localScope): self
    {
        $this->localOnly = $localScope;
        $this->scope = $localScope ? $this->worksheet : null;

        return $this;
    }

    
    public function getScope(): ?Worksheet
    {
        return $this->scope;
    }

    
    public function setScope(?Worksheet $worksheet): self
    {
        $this->scope = $worksheet;
        $this->localOnly = $worksheet !== null;

        return $this;
    }

    
    public function isFormula(): bool
    {
        return $this->isFormula;
    }

    
    public static function resolveName(string $definedName, Worksheet $worksheet, string $sheetName = ''): ?self
    {
        if ($sheetName === '') {
            $worksheet2 = $worksheet;
        } else {
            $worksheet2 = $worksheet->getParentOrThrow()->getSheetByName($sheetName);
            if ($worksheet2 === null) {
                return null;
            }
        }

        return $worksheet->getParentOrThrow()->getDefinedName($definedName, $worksheet2);
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                $this->$key = clone $value;
            } else {
                $this->$key = $value;
            }
        }
    }
}
