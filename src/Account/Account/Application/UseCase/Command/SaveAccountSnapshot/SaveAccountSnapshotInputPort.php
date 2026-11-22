<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface SaveAccountSnapshotInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;
}
