<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestIdentityReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutput;
use Tests\TestCase;

class GetAccountCategoryChangeRequestOutputTest extends TestCase
{
    public function testToArrayReturnsDetailPayload(): void
    {
        $output = new GetAccountCategoryChangeRequestOutput();
        $output->output(new AccountCategoryChangeRequestDetailReadModel(
            request: new AccountCategoryChangeRequestReadModel(
                requestIdentifier: 'request-id',
                accountIdentifier: 'account-id',
                currentAccountCategory: 'general',
                requestedAccountCategory: 'agency',
                status: 'pending',
                requestedAt: '2026-08-11T10:00:00+00:00',
                reviewedBy: null,
                reviewedAt: null,
                rejectionReason: null,
            ),
            account: new AccountReadModel(
                accountIdentifier: 'account-id',
                email: 'account@example.com',
                type: 'individual',
                name: 'Account Name',
                status: 'active',
                accountCategory: 'general',
            ),
            identities: [new AccountCategoryChangeRequestIdentityReadModel('Alice', 'alice@example.com')],
            documents: [new AccountDocumentReadModel('identity', 'documents/identity.png', '2026-08-11T11:00:00+00:00')],
        ));

        $this->assertSame([
            'request' => [
                'requestIdentifier' => 'request-id',
                'accountIdentifier' => 'account-id',
                'currentAccountCategory' => 'general',
                'requestedAccountCategory' => 'agency',
                'status' => 'pending',
                'requestedAt' => '2026-08-11T10:00:00+00:00',
                'reviewedBy' => null,
                'reviewedAt' => null,
                'rejectionReason' => null,
            ],
            'account' => [
                'accountIdentifier' => 'account-id',
                'email' => 'account@example.com',
                'type' => 'individual',
                'name' => 'Account Name',
                'status' => 'active',
                'accountCategory' => 'general',
            ],
            'identities' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
            ],
            'documents' => [
                ['documentType' => 'identity', 'documentPath' => 'documents/identity.png', 'uploadedAt' => '2026-08-11T11:00:00+00:00'],
            ],
        ], $output->toArray());
    }
}
