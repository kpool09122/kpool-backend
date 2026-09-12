<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Http\Action\Command\ApproveDelegation;

use Application\Http\Action\Account\Delegation\Command\ApproveDelegation\ApproveDelegationAction;
use Application\Http\Action\Account\Delegation\Command\ApproveDelegation\ApproveDelegationRequest;
use Application\Http\Context\AccountContext;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationOutput;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
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

class ApproveDelegationActionTest extends TestCase
{
    public function testRequestBodyIsEmpty(): void
    {
        $this->assertSame([], (new ApproveDelegationRequest())->rules());
    }

    public function testUsesAccountContextPrincipal(): void
    {
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $talent);
        $id = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegation = new AccountDelegation($id, new AffiliationIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), $talent, new AccountIdentifier(StrTestHelper::generateUuid()), DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), new DateTimeImmutable(), null);
        /** @var ApproveDelegationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ApproveDelegationRequest::class);
        $request->shouldReceive('delegationId')->andReturn((string) $id);
        $request->shouldReceive('language')->andReturn('en');
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        /** @var ApproveDelegationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ApproveDelegationInterface::class);
        $useCase->shouldReceive('process')->with(Mockery::on(fn ($input) => $input instanceof ApproveDelegationInput && $input->principal() === $principal), Mockery::on(function ($output) use ($delegation) {
            if (! $output instanceof ApproveDelegationOutput) {
                return false;
            } $output->setDelegation($delegation);

            return true;
        }))->once();
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');
        $context = new AccountContext($principal, AccountType::INDIVIDUAL, AccountCategory::TALENT);
        $response = (new ApproveDelegationAction($useCase, $context, $logger))($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
