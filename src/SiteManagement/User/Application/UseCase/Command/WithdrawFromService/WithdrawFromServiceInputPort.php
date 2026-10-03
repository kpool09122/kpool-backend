<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\WithdrawFromService;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface WithdrawFromServiceInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
