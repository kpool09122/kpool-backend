<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Factory;

use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface InvitationFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        IdentityIdentifier $invitedByIdentityIdentifier,
        Email $email
    ): Invitation;
}
