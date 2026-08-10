<?php

declare(strict_types=1);

namespace Tests\Account\Account\Http\Action\Command\RequestAccountTypeChange;

use Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\RequestAccountTypeChange\RequestAccountTypeChangeAction;
use Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\RequestAccountTypeChange\RequestAccountTypeChangeRequest;
use Application\Http\Context\AccountContext;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\SameAccountTypeChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange\RequestAccountTypeChangeOutput;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAccountTypeChangeActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestEntity = new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid()),
            $accountId,
            AccountType::INDIVIDUAL,
            AccountType::CORPORATION,
            AccountTypeChangeRequestStatus::PENDING,
            new DateTimeImmutable('2026-08-11 00:00:00'),
            null,
            null,
            null,
        );

        /** @var RequestAccountTypeChangeRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestAccountTypeChangeRequest::class);
        $request->shouldReceive('accountIdentifier')->andReturn((string) $accountId);
        $request->shouldReceive('requestedAccountType')->andReturn(AccountType::CORPORATION->value);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RequestAccountTypeChangeInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAccountTypeChangeInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(RequestAccountTypeChangeInput::class),
                Mockery::on(static function ($output) use ($requestEntity): bool {
                    if (! $output instanceof RequestAccountTypeChangeOutput) {
                        return false;
                    }
                    $output->setRequest($requestEntity);

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $response = (new RequestAccountTypeChangeAction($useCase, $this->createAccountContext($accountId), $logger))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame((string) $requestEntity->requestIdentifier(), $payload['requestIdentifier']);
        $this->assertSame(AccountTypeChangeRequestStatus::PENDING->value, $payload['status']);
    }

    public function testInvokeReturnsNotFoundResponseWhenAccountIsMissing(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var RequestAccountTypeChangeRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestAccountTypeChangeRequest::class);
        $request->shouldReceive('accountIdentifier')->andReturn((string) $accountId);
        $request->shouldReceive('requestedAccountType')->andReturn(AccountType::CORPORATION->value);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RequestAccountTypeChangeInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAccountTypeChangeInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new AccountNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $response = (new RequestAccountTypeChangeAction($useCase, $this->createAccountContext($accountId), $logger))($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testInvokeReturnsUnprocessableEntityResponseWhenRequestedTypeIsSame(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var RequestAccountTypeChangeRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestAccountTypeChangeRequest::class);
        $request->shouldReceive('accountIdentifier')->andReturn((string) $accountId);
        $request->shouldReceive('requestedAccountType')->andReturn(AccountType::INDIVIDUAL->value);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RequestAccountTypeChangeInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAccountTypeChangeInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new SameAccountTypeChangeRequestException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $response = (new RequestAccountTypeChangeAction($useCase, $this->createAccountContext($accountId), $logger))($request);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    private function createAccountContext(AccountIdentifier $accountId): AccountContext
    {
        return new AccountContext(
            new Principal(
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                new IdentityIdentifier(StrTestHelper::generateUuid()),
                $accountId,
            ),
            AccountType::INDIVIDUAL,
        );
    }
}
