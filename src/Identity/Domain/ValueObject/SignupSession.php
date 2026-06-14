<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;

readonly class SignupSession
{
    public function __construct(
        private ?AccountType $accountType = null,
        private ?InvitationToken $invitationToken = null,
        private ?string $returnTo = null,
    ) {
    }

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }

    public function invitationToken(): ?InvitationToken
    {
        return $this->invitationToken;
    }

    public function returnTo(): ?string
    {
        return $this->returnTo;
    }
}
