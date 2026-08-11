<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\ListAccountDocuments;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsOutput;

class ListAccountDocumentsOutputTest extends TestCase
{
    public function testToArrayReturnsDocuments(): void
    {
        $output = new ListAccountDocumentsOutput();
        $output->output([
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
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyDocumentsByDefault(): void
    {
        $output = new ListAccountDocumentsOutput();

        $this->assertSame(['documents' => []], $output->toArray());
    }
}
