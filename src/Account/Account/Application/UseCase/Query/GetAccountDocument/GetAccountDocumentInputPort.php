<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountDocument;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GetAccountDocumentInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function documentType(): string;

    public function principal(): Principal;
}
