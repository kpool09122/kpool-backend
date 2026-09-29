<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Exception;

use DomainException;
use Source\Account\Principal\Domain\Entity\Role;
use Throwable;

class SystemRoleNotFoundException extends DomainException
{
    public static function owner(?Throwable $previous = null): self
    {
        return new self(Role::OWNER, $previous);
    }

    public function __construct(
        string $roleName,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('%s account role is not found.', ucfirst($roleName)), 0, $previous);
    }
}
