<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentListReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;

class AccountDocumentListReadModelTest extends TestCase
{
    public function testToArrayReturnsDocuments(): void
    {
        $list = new AccountDocumentListReadModel([
            new AccountDocumentReadModel(
                documentType: 'representative_id',
                documentPath: 'accounts/account-id/representative_id.pdf',
                uploadedAt: '2026-07-30T12:34:56+00:00',
            ),
        ]);

        $this->assertSame([
            'documents' => [[
                'documentType' => 'representative_id',
                'documentPath' => 'accounts/account-id/representative_id.pdf',
                'uploadedAt' => '2026-07-30T12:34:56+00:00',
            ]],
        ], $list->toArray());
    }

    public function testToArrayReturnsEmptyDocuments(): void
    {
        $list = new AccountDocumentListReadModel([]);

        $this->assertSame(['documents' => []], $list->toArray());
    }
}
