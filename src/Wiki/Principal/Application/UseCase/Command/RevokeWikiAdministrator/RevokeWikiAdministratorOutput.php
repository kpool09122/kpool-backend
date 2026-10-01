<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

class RevokeWikiAdministratorOutput implements RevokeWikiAdministratorOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
