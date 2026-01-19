<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreateInvitationInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function inviterIdentityIdentifier(): IdentityIdentifier;

    /**
     * @return array<Email>
     */
    public function emails(): array;
}
