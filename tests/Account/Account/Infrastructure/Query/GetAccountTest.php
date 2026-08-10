<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInput;
use Source\Account\Account\Application\UseCase\Query\GetAccount\GetAccountInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Infrastructure\Query\GetAccount;
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

class GetAccountTest extends TestCase
{
    public function test__construct(): void
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $useCase = $this->app->make(GetAccountInterface::class);

        $this->assertInstanceOf(GetAccount::class, $useCase);
    }

    #[Group('useDb')]
    public function testProcessReturnsAccountReadModelForPermittedPrincipal(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        CreateAccount::create((string) $accountIdentifier, [
            'email' => 'query-account@example.com',
            'type' => 'corporation',
            'name' => 'Query Account Inc',
            'status' => 'active',
            'category' => 'agency',
        ]);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::READ, Mockery::on(
                static fn (Resource $resource): bool => $resource->accountType() === AccountType::CORPORATION
            ))
            ->andReturnTrue();

        $readModel = (new GetAccount($policyEvaluator))->process(new GetAccountInput($accountIdentifier, $principal, AccountType::CORPORATION));

        $this->assertInstanceOf(AccountReadModel::class, $readModel);
        $this->assertSame((string) $accountIdentifier, $readModel->accountIdentifier());
        $this->assertSame('query-account@example.com', $readModel->email());
        $this->assertSame('corporation', $readModel->type());
        $this->assertSame('Query Account Inc', $readModel->name());
        $this->assertSame('active', $readModel->status());
        $this->assertSame('agency', $readModel->accountCategory());
    }

    #[Group('useDb')]
    public function testProcessThrowsAccountNotFoundException(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::READ, Mockery::on(
                static fn (Resource $resource): bool => $resource->accountType() === AccountType::CORPORATION
            ))
            ->andReturnTrue();

        $this->expectException(AccountNotFoundException::class);

        (new GetAccount($policyEvaluator))->process(new GetAccountInput($accountIdentifier, $principal, AccountType::CORPORATION));
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenForDifferentAccountPrincipal(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldNotReceive('evaluate');

        $this->expectException(AccountUpdateForbiddenException::class);

        (new GetAccount($policyEvaluator))->process(new GetAccountInput(
            $accountIdentifier,
            $this->createPrincipal(new AccountIdentifier(StrTestHelper::generateUuid())),
        ));
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenWhenPolicyDenies(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        CreateAccount::create((string) $accountIdentifier);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountUpdateForbiddenException::class);

        (new GetAccount($policyEvaluator))->process(new GetAccountInput($accountIdentifier, $principal));
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
