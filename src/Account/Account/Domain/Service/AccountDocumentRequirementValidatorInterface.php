<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Service;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DocumentType;

interface AccountDocumentRequirementValidatorInterface
{
    /** @param DocumentType[] $documentTypes */
    public function validate(AccountType $accountType, array $documentTypes): void;
}
