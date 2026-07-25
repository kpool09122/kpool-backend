<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface InviteMemberInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function inviterIdentityIdentifier(): IdentityIdentifier;

    /**
     * @return array<Email>
     */
    public function emails(): array;
}
