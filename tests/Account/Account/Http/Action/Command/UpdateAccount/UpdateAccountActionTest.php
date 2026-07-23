<?php

declare(strict_types=1);

namespace Tests\Account\Account\Http\Action\Command\UpdateAccount;

use Application\Http\Action\Account\Account\Command\UpdateAccount\UpdateAccountAction;
use Application\Http\Action\Account\Account\Command\UpdateAccount\UpdateAccountRequest;
use Application\Http\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateAccountActionTest extends TestCase
{
    public function testInvokeReturnsOkResponse(): void
    {
        $account = $this->createAccount(accountName: 'Updated Account');
        $request = $this->request((string) $account->accountIdentifier(), 'Updated Account');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var UpdateAccountInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(UpdateAccountInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(UpdateAccountInput::class),
                Mockery::on(function ($output) use ($account): bool {
                    if (! $output instanceof UpdateAccountOutput) {
                        return false;
                    }

                    $output->setAccount($account);

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $response = (new UpdateAccountAction($useCase, $this->actorContext(), $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame((string) $account->accountIdentifier(), $payload['accountIdentifier']);
        $this->assertSame('Updated Account', $payload['name']);
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        $request = $this->request(StrTestHelper::generateUuid(), 'Updated Account');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var UpdateAccountInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(UpdateAccountInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new AccountNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $response = (new UpdateAccountAction($useCase, $this->actorContext(), $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('account_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsForbiddenResponse(): void
    {
        $request = $this->request(StrTestHelper::generateUuid(), 'Updated Account');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var UpdateAccountInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(UpdateAccountInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new AccountUpdateForbiddenException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $response = (new UpdateAccountAction($useCase, $this->actorContext(), $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(error_message('account_update_forbidden', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsUnprocessableEntityResponseForInvalidAccountName(): void
    {
        $request = $this->request(StrTestHelper::generateUuid(), '   ');

        DB::shouldReceive('beginTransaction')->never();

        /** @var UpdateAccountInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(UpdateAccountInterface::class);
        $useCase->shouldNotReceive('process');

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $response = (new UpdateAccountAction($useCase, $this->actorContext(), $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $payload['status']);
    }

    private function request(string $accountId, string $accountName): UpdateAccountRequest
    {
        /** @var UpdateAccountRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(UpdateAccountRequest::class);
        $request->shouldReceive('accountId')->andReturn($accountId);
        $request->shouldReceive('accountName')->andReturn($accountName);
        $request->shouldReceive('language')->andReturn('en');

        return $request;
    }

    private function actorContext(): ActorContext
    {
        return new ActorContext(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            null,
            null,
        );
    }

    private function createAccount(string $accountName): Account
    {
        return new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            AccountType::CORPORATION,
            new AccountName($accountName),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );
    }
}
