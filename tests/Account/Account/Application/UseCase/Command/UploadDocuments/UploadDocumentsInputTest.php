<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UploadDocuments;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\DocumentData;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInput;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class UploadDocumentsInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principal = new Principal(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'));
        $documents = [new DocumentData(DocumentType::PASSPORT, 'pdf-content')];

        $subject = new UploadDocumentsInput($accountIdentifier, $principal, $documents);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($principal, $subject->principal());
        $this->assertSame($documents, $subject->documents());
    }
}
