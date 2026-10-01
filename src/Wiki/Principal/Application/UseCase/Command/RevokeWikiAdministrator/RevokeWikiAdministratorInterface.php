<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

interface RevokeWikiAdministratorInterface
{
    public function process(RevokeWikiAdministratorInputPort $input, RevokeWikiAdministratorOutputPort $output): void;
}
