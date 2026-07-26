<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

interface InviteMemberInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function inviterPrincipalIdentifier(): PrincipalIdentifier;

    /**
     * @return array<Email>
     */
    public function emails(): array;
}
