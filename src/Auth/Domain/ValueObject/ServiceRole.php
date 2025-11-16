<?php

declare(strict_types=1);

namespace Source\Auth\Domain\ValueObject;

use InvalidArgumentException;

readonly class ServiceRole
{
    public function __construct(
        private string $service,
        private string $role,
    ) {
        $this->validate($service, $role);
    }

    public function service(): string
    {
        return $this->service;
    }

    public function role(): string
    {
        return $this->role;
    }

    protected function validate(
        string $service,
        string $role
    ): void {
        if ($service === '') {
            throw new InvalidArgumentException('Service cannot be empty.');
        }

        if ($role === '') {
            throw new InvalidArgumentException('Role cannot be empty.');
        }
    }
}
