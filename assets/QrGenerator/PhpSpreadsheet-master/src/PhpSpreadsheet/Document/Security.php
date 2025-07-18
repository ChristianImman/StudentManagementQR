<?php

namespace PhpOffice\PhpSpreadsheet\Document;

use PhpOffice\PhpSpreadsheet\Shared\PasswordHasher;

class Security
{
    
    private bool $lockRevision = false;

    
    private bool $lockStructure = false;

    
    private bool $lockWindows = false;

    
    private string $revisionsPassword = '';

    
    private string $workbookPassword = '';

    
    public function __construct()
    {
    }

    
    public function isSecurityEnabled(): bool
    {
        return $this->lockRevision
                || $this->lockStructure
                || $this->lockWindows;
    }

    public function getLockRevision(): bool
    {
        return $this->lockRevision;
    }

    public function setLockRevision(?bool $locked): self
    {
        if ($locked !== null) {
            $this->lockRevision = $locked;
        }

        return $this;
    }

    public function getLockStructure(): bool
    {
        return $this->lockStructure;
    }

    public function setLockStructure(?bool $locked): self
    {
        if ($locked !== null) {
            $this->lockStructure = $locked;
        }

        return $this;
    }

    public function getLockWindows(): bool
    {
        return $this->lockWindows;
    }

    public function setLockWindows(?bool $locked): self
    {
        if ($locked !== null) {
            $this->lockWindows = $locked;
        }

        return $this;
    }

    public function getRevisionsPassword(): string
    {
        return $this->revisionsPassword;
    }

    
    public function setRevisionsPassword(?string $password, bool $alreadyHashed = false): static
    {
        if ($password !== null) {
            if (!$alreadyHashed) {
                $password = PasswordHasher::hashPassword($password);
            }
            $this->revisionsPassword = $password;
        }

        return $this;
    }

    public function getWorkbookPassword(): string
    {
        return $this->workbookPassword;
    }

    
    public function setWorkbookPassword(?string $password, bool $alreadyHashed = false): static
    {
        if ($password !== null) {
            if (!$alreadyHashed) {
                $password = PasswordHasher::hashPassword($password);
            }
            $this->workbookPassword = $password;
        }

        return $this;
    }
}
