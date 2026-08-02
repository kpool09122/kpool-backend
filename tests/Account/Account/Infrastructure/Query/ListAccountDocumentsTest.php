<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountDocumentListForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInput;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInterface;
use Source\Account\Account\Infrastructure\Query\ListAccountDocuments;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListAccountDocumentsTest extends TestCase
{
    public function test__construct(): void
    {
        $useCase = $this->app->make(ListAccountDocumentsInterface::class);

        $this->assertInstanceOf(ListAccountDocuments::class, $useCase);
    }

    #[Group('useDb')]
    public function testProcessReturnsDocumentsOrderedByDocumentType(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        CreateAccount::create((string) $accountIdentifier);

        DB::table('account_documents')->insert([
            [
                'account_id' => (string) $accountIdentifier,
                'document_type' => 'representative_id',
                'document_path' => 'accounts/account-id/representative_id.pdf',
                'uploaded_at' => '2026-07-30 12:34:56',
            ],
            [
                'account_id' => (string) $accountIdentifier,
                'document_type' => 'corporate_register',
                'document_path' => 'accounts/account-id/corporate_register.pdf',
                'uploaded_at' => '2026-07-29 01:02:03',
            ],
        ]);

        $readModel = (new ListAccountDocuments())->process(new ListAccountDocumentsInput($accountIdentifier, $principal));

        $this->assertSame([
            'documents' => [
                [
                    'documentType' => 'corporate_register',
                    'documentPath' => 'accounts/account-id/corporate_register.pdf',
                    'uploadedAt' => '2026-07-29T01:02:03+00:00',
                ],
                [
                    'documentType' => 'representative_id',
                    'documentPath' => 'accounts/account-id/representative_id.pdf',
                    'uploadedAt' => '2026-07-30T12:34:56+00:00',
                ],
            ],
        ], $readModel->toArray());
    }

    #[Group('useDb')]
    public function testProcessReturnsEmptyDocumentsForAccountWithoutDocuments(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        CreateAccount::create((string) $accountIdentifier);

        $readModel = (new ListAccountDocuments())->process(new ListAccountDocumentsInput($accountIdentifier, $principal));

        $this->assertSame(['documents' => []], $readModel->toArray());
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenForDifferentAccountPrincipal(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $this->expectException(AccountDocumentListForbiddenException::class);

        (new ListAccountDocuments())->process(new ListAccountDocumentsInput(
            $accountIdentifier,
            $this->createPrincipal(new AccountIdentifier(StrTestHelper::generateUuid())),
        ));
    }

    #[Group('useDb')]
    public function testProcessThrowsAccountNotFoundException(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);

        $this->expectException(AccountNotFoundException::class);

        (new ListAccountDocuments())->process(new ListAccountDocumentsInput($accountIdentifier, $principal));
    }

    private function createPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
    }
}
