<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Repository;

use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

interface InvitationRepositoryInterface
{
    public function save(Invitation $invitation): void;

    public function findByToken(OneTimeToken $token): ?Invitation;

    public function findPendingByAccountAndEmail(
        AccountIdentifier $accountIdentifier,
        Email $email
    ): ?Invitation;
}
