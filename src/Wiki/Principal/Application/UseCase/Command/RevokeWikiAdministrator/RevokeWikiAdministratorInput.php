<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

use Source\Shared\Domain\ValueObject\Email;

readonly class RevokeWikiAdministratorInput implements RevokeWikiAdministratorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
