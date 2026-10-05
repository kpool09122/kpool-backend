<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\EventHandler;

use Source\Account\Principal\Domain\Event\PrincipalCreated;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;

readonly class PrincipalCreatedHandler
{
    public function __construct(private ProvisionPrincipalInterface $provisionPrincipal)
    {
    }

    public function handle(PrincipalCreated $event): void
    {
        $this->provisionPrincipal->process(new ProvisionPrincipalInput($event->identityIdentifier), new ProvisionPrincipalOutput());
    }
}
