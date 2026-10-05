<?php

declare(strict_types=1);

namespace Tests\Http\Action\Account;

use Application\Http\Action\Account\Account\Query\ViewAccountDocument\ViewAccountDocumentAction;
use Application\Http\Action\Account\Account\Query\ViewAccountDocument\ViewAccountDocumentRequest;
use Application\Http\Action\Account\Account\Query\ViewMyAccountDocument\ViewMyAccountDocumentAction;
use Application\Http\Action\Account\Account\Query\ViewMyAccountDocument\ViewMyAccountDocumentRequest;
use Application\Http\Context\AccountContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Infrastructure\Query\GetAccountDocument;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DocumentStreamingTest extends TestCase
{
    public function testOwnerReceivesPrivateUncachedStream(): void
    {
        $context = $this->context();
        $this->document((string) $context->principal()->accountIdentifier());
        /** @var ViewMyAccountDocumentRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ViewMyAccountDocumentRequest::class);
        $request->shouldReceive('documentType')->andReturn('business_registration');
        $response = (new ViewMyAccountDocumentAction($context, new NullLogger()))($request);
        self::assertInstanceOf(StreamedResponse::class, $response);
        $this->assertPrivateStream($response);
    }

    public function testOwnerCannotReadAnotherAccountsDocument(): void
    {
        $this->document(StrTestHelper::generateUuid());
        /** @var ViewMyAccountDocumentRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ViewMyAccountDocumentRequest::class);
        $request->shouldReceive('documentType')->andReturn('business_registration');
        $response = (new ViewMyAccountDocumentAction($this->context(), new NullLogger()))($request);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testAllowedReviewerReceivesPrivateUncachedStream(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->document($accountId);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();
        $response = (new ViewAccountDocumentAction(new GetAccountDocument($policyEvaluator), $this->context(), new NullLogger()))($this->reviewRequest($accountId));
        self::assertInstanceOf(StreamedResponse::class, $response);
        $this->assertPrivateStream($response);
    }

    public function testMissingObjectReturns404ForOwnerAndAllowedReviewer(): void
    {
        $context = $this->context();
        $accountId = (string) $context->principal()->accountIdentifier();
        $this->document($accountId);
        Storage::disk('verification-documents')->delete('accounts/' . $accountId . '/document.pdf');

        /** @var ViewMyAccountDocumentRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ViewMyAccountDocumentRequest::class);
        $request->shouldReceive('documentType')->andReturn('business_registration');
        $response = (new ViewMyAccountDocumentAction($context, new NullLogger()))($request);
        self::assertSame(404, $response->getStatusCode());

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();
        $response = (new ViewAccountDocumentAction(new GetAccountDocument($policyEvaluator), $context, new NullLogger()))($this->reviewRequest($accountId));
        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeniedReviewerNeverReadsStorage(): void
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->never();
        $response = (new ViewAccountDocumentAction(new GetAccountDocument($policyEvaluator), $this->context(), new NullLogger()))($this->reviewRequest(StrTestHelper::generateUuid()));
        self::assertSame(403, $response->getStatusCode());
    }

    private function assertPrivateStream(StreamedResponse $response): void
    {
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        ob_start();
        $response->sendContent();
        self::assertSame('private document', ob_get_clean());
        self::assertFalse($response->headers->has('Location'));
    }

    private function document(string $accountId): void
    {
        CreateAccount::create($accountId);
        DB::table('account_documents')->insert([
            'account_id' => $accountId,
            'document_type' => 'business_registration',
            'document_path' => 'accounts/' . $accountId . '/document.pdf',
            'uploaded_at' => now(),
        ]);
        Storage::fake('verification-documents')->put('accounts/' . $accountId . '/document.pdf', 'private document');
        Storage::fake('s3')->assertDirectoryEmpty('/');
    }

    private function reviewRequest(string $accountId): ViewAccountDocumentRequest
    {
        /** @var ViewAccountDocumentRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ViewAccountDocumentRequest::class);
        $request->shouldReceive('accountId')->andReturn($accountId);
        $request->shouldReceive('documentType')->andReturn('business_registration');
        $request->shouldReceive('language')->andReturn('ja');

        return $request;
    }

    private function context(): AccountContext
    {
        return new AccountContext(
            new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid())),
            null,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
        );
    }
}
