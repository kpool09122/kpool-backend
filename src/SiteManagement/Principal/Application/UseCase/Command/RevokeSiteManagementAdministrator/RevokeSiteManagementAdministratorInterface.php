<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

interface RevokeSiteManagementAdministratorInterface
{
    public function process(RevokeSiteManagementAdministratorInputPort $input, RevokeSiteManagementAdministratorOutputPort $output): void;
}
