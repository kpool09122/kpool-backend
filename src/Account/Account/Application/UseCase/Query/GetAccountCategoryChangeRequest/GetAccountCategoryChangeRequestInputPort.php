<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

interface GetAccountCategoryChangeRequestInputPort
{
    public function requestIdentifier(): AccountCategoryChangeRequestIdentifier;

    public function principal(): Principal;
}
