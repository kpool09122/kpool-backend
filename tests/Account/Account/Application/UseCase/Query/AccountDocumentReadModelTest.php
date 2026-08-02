<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;

class AccountDocumentReadModelTest extends TestCase
{
    public function testToArrayReturnsDocumentFields(): void
    {
        $document = new AccountDocumentReadModel(
            documentType: 'corporate_register',
            documentPath: 'accounts/account-id/corporate_register.pdf',
            uploadedAt: '2026-07-30T12:34:56+00:00',
        );

        $this->assertSame([
            'documentType' => 'corporate_register',
            'documentPath' => 'accounts/account-id/corporate_register.pdf',
            'uploadedAt' => '2026-07-30T12:34:56+00:00',
        ], $document->toArray());
    }
}
