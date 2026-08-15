<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Service;

use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Shared\Domain\ValueObject\AccountType;

interface AccountDocumentRequirementValidatorInterface
{
    /** @param DocumentType[] $documentTypes */
    public function validate(AccountType $accountType, array $documentTypes): void;
}
