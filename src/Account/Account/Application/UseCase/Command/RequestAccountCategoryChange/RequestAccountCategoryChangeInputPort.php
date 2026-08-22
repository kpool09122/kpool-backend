<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestAccountCategoryChangeInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function requestedAccountCategory(): AccountCategory;
}
