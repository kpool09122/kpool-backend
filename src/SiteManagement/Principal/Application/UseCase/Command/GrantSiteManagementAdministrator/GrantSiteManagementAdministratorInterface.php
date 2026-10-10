<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

interface GrantSiteManagementAdministratorInterface
{
    public function process(GrantSiteManagementAdministratorInputPort $input, GrantSiteManagementAdministratorOutputPort $output): void;
}
