<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\Service;

use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountResource;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PolicyEvaluatorInterface
{
    public function evaluate(
        IdentityIdentifier $actorIdentityIdentifier,
        AccountAction $action,
        AccountResource $resource,
    ): bool;
}
