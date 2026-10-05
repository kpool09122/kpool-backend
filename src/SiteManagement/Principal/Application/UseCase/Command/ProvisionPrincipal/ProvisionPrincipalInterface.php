<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

interface ProvisionPrincipalInterface
{
    /**
     * @param ProvisionPrincipalInputPort $inputPort
     */
    public function process(ProvisionPrincipalInputPort $inputPort, ProvisionPrincipalOutputPort $output): void;
}
