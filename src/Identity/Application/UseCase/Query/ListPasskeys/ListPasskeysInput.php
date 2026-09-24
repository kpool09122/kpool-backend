<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListPasskeys;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListPasskeysInput implements ListPasskeysInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
