<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

interface RevokeSiteManagementOperatorInterface
{
    public function process(RevokeSiteManagementOperatorInputPort $input, RevokeSiteManagementOperatorOutputPort $output): void;
}
