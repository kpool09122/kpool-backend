<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

interface GrantWikiAdministratorInterface
{
    public function process(GrantWikiAdministratorInputPort $input, GrantWikiAdministratorOutputPort $output): void;
}
