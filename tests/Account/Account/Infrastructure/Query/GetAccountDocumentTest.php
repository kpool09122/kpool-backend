<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountDocumentNotFoundException;
use Source\Account\Account\Application\Exception\AccountDocumentViewForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInterface;
use Source\Account\Account\Infrastructure\Query\GetAccountDocument;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAccountDocumentTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(GetAccountDocument::class, $this->app->make(GetAccountDocumentInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsAccountDocumentReadModelWhenOperatorIsAllowed(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $this->insertDocument((string) $accountIdentifier, 'business_registration', 'accounts/target/business_registration.pdf');

        $document = (new GetAccountDocument($this->allowingPolicyEvaluator($operator)))
            ->process(new GetAccountDocumentInput($accountIdentifier, 'business_registration', $operator));

        $this->assertSame('business_registration', $document->documentType());
        $this->assertSame('accounts/target/business_registration.pdf', $document->documentPath());
        $this->assertSame('2026-08-11T12:00:00+00:00', $document->uploadedAt());
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenWhenPolicyDoesNotAllow(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountDocumentViewForbiddenException::class);

        (new GetAccountDocument($policyEvaluator))
            ->process(new GetAccountDocumentInput($accountIdentifier, 'business_registration', $operator));
    }

    #[Group('useDb')]
    public function testProcessThrowsAccountNotFoundWhenAccountDoesNotExist(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $this->expectException(AccountNotFoundException::class);

        (new GetAccountDocument($this->allowingPolicyEvaluator($operator)))
            ->process(new GetAccountDocumentInput($accountIdentifier, 'business_registration', $operator));
    }

    #[Group('useDb')]
    public function testProcessThrowsDocumentNotFoundWhenDocumentDoesNotExist(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $this->expectException(AccountDocumentNotFoundException::class);

        (new GetAccountDocument($this->allowingPolicyEvaluator($operator)))
            ->process(new GetAccountDocumentInput($accountIdentifier, 'business_registration', $operator));
    }

    private function allowingPolicyEvaluator(Principal $principal): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with($principal, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $principal->accountIdentifier()))
            ->andReturnTrue();

        return $policyEvaluator;
    }

    private function insertDocument(string $accountId, string $documentType, string $documentPath): void
    {
        DB::table('account_documents')->insert([
            'account_id' => $accountId,
            'document_type' => $documentType,
            'document_path' => $documentPath,
            'uploaded_at' => '2026-08-11 12:00:00',
        ]);
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
    }
}
