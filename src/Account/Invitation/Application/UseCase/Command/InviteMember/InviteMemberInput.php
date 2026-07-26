<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

readonly class InviteMemberInput implements InviteMemberInputPort
{
    /**
     * @param array<Email> $emails
     */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private PrincipalIdentifier $inviterPrincipalIdentifier,
        private array $emails,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function inviterPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->inviterPrincipalIdentifier;
    }

    /**
     * @return array<Email>
     */
    public function emails(): array
    {
        return $this->emails;
    }
}
