<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

use Source\Shared\Domain\ValueObject\Email;

readonly class GrantWikiAdministratorInput implements GrantWikiAdministratorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
