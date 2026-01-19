<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreateInvitationInput implements CreateInvitationInputPort
{
    /**
     * @param array<Email> $emails
     */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private IdentityIdentifier $inviterIdentityIdentifier,
        private array $emails,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function inviterIdentityIdentifier(): IdentityIdentifier
    {
        return $this->inviterIdentityIdentifier;
    }

    /**
     * @return array<Email>
     */
    public function emails(): array
    {
        return $this->emails;
    }
}
