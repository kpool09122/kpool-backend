<?php

declare(strict_types=1);

namespace Tests\Account\AccountDelegation\Http\Action\Command\RequestDelegation;

use Application\Http\Action\Account\Delegation\Command\RequestDelegation\RequestDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RequestDelegation\RequestDelegationRequest;
use Application\Http\Context\AccountContext;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationForbiddenException;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationInput;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationInterface;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationOutput;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestDelegationActionTest extends TestCase
{
    public function testRequestContractOnlyContainsTargetAccountIdentifier(): void
    {
        $rules = (new RequestDelegationRequest())->rules();

        $this->assertSame(['targetAccountIdentifier'], array_keys($rules));
        $this->assertSame(['required', 'uuid'], $rules['targetAccountIdentifier']);
    }

    public function testReturnsCreatedWithAccountLevelResponse(): void
    {
        $principal = $this->principal();
        $target = new AccountIdentifier(StrTestHelper::generateUuid());
        $delegation = $this->delegation($principal->accountIdentifier(), $target);
        $request = $this->request((string) $target);
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RequestAccountDelegationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAccountDelegationInterface::class);
        $useCase->shouldReceive('process')->once()->with(
            Mockery::on(fn ($input) => $input instanceof RequestAccountDelegationInput
                && $input->principal() === $principal
                && (string) $input->targetAccountIdentifier() === (string) $target),
            Mockery::on(function ($output) use ($delegation): bool {
                if (! $output instanceof RequestAccountDelegationOutput) {
                    return false;
                }
                $output->setDelegation($delegation);

                return true;
            }),
        );
        $response = (new RequestDelegationAction($useCase, $this->context($principal), $this->logger(false)))($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame((string) $target, $payload['delegatorAccountIdentifier']);
        $this->assertArrayHasKey('requestedByAccountIdentifier', $payload);
        $this->assertArrayNotHasKey('delegateIdentifier', $payload);
    }

    public function testReturnsForbiddenWhenPolicyDenies(): void
    {
        $this->assertErrorResponse(new AccountDelegationForbiddenException(), Response::HTTP_FORBIDDEN);
    }

    public function testReturnsConflictForDuplicateOpenRequest(): void
    {
        $this->assertErrorResponse(new AccountDelegationAlreadyExistsException(), Response::HTTP_CONFLICT);
    }

    private function assertErrorResponse(\Throwable $exception, int $expectedStatus): void
    {
        $principal = $this->principal();
        $request = $this->request(StrTestHelper::generateUuid());
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        /** @var RequestAccountDelegationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAccountDelegationInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow($exception);

        $response = (new RequestDelegationAction($useCase, $this->context($principal), $this->logger(true)))($request);
        $this->assertSame($expectedStatus, $response->getStatusCode());
    }

    /** @return RequestDelegationRequest&Mockery\MockInterface */
    private function request(string $target): RequestDelegationRequest
    {
        /** @var RequestDelegationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestDelegationRequest::class);
        $request->shouldReceive('targetAccountIdentifier')->andReturn($target);
        $request->shouldReceive('language')->andReturn('en');

        return $request;
    }

    /** @return LoggerInterface&Mockery\MockInterface */
    private function logger(bool $expectsError): LoggerInterface
    {
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $expectsError ? $logger->shouldReceive('error')->once() : $logger->shouldNotReceive('error');

        return $logger;
    }

    private function context(Principal $principal): AccountContext
    {
        return new AccountContext($principal, AccountType::CORPORATION, AccountCategory::AGENCY);
    }

    private function principal(): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
    }

    private function delegation(AccountIdentifier $agency, AccountIdentifier $talent): AccountDelegation
    {
        return new AccountDelegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agency,
            $talent,
            $agency,
            DelegationStatus::PENDING,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
