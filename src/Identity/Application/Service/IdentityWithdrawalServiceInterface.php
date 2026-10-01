<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityWithdrawalServiceInterface
{
    public function archive(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void;

    public function delete(IdentityIdentifier $identityIdentifier): void;
}
