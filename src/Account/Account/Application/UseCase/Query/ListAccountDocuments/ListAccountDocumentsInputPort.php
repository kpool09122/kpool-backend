<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountDocuments;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListAccountDocumentsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;
}
