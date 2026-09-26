<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Http\Action\Command\RejectDelegation;

use Application\Http\Action\Account\Delegation\Command\RejectDelegation\RejectDelegationAction;
use Application\Http\Action\Account\Delegation\Command\RejectDelegation\RejectDelegationRequest;
use Application\Http\Context\AccountContext;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectDelegationActionTest extends TestCase
{
    public function testRequestBodyIsEmpty(): void
    {
        $this->assertSame([], (new RejectDelegationRequest())->rules());
    }

    public function testUsesAccountContextPrincipal(): void
    {
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        /** @var RejectDelegationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectDelegationRequest::class);
        $request->shouldReceive('delegationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        /** @var RejectDelegationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectDelegationInterface::class);
        $useCase->shouldReceive('process')->with(Mockery::on(fn ($input) => $input instanceof RejectDelegationInput && $input->principal() === $principal))->once();
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');
        $response = (new RejectDelegationAction($useCase, new AccountContext($principal, AccountType::INDIVIDUAL, AccountCategory::TALENT), $logger))($request);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
