<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListPasskeys;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ListPasskeysInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
