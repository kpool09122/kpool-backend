<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Application\Exception\MonetizationAccountAlreadyExistsException;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInput;
use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountInterface;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Factory\MonetizationAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionMonetizationAccountTest extends TestCase
{
    /**
     * 正常系: 正しくMonetizationAccountを作成できること
     *
     * @throws MonetizationAccountAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());

        $input = new ProvisionMonetizationAccountInput($accountIdentifier);

        $expectedAccount = new MonetizationAccount(
            $monetizationAccountIdentifier,
            $accountIdentifier,
            [],
            null,
            null,
        );

        $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $repository->shouldReceive('findByAccountIdentifier')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(MonetizationAccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($expectedAccount);

        $repository->shouldReceive('save')
            ->with($expectedAccount)
            ->once();

        $this->app->instance(MonetizationAccountFactoryInterface::class, $factory);
        $this->app->instance(MonetizationAccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(ProvisionMonetizationAccountInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $monetizationAccountIdentifier, (string) $result->monetizationAccountIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $result->accountIdentifier());
        $this->assertEmpty($result->capabilities());
    }

    /**
     * 異常系: すでに存在するMonetizationAccountを作成しようとした場合、例外がスローされること
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenAccountAlreadyExists(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());

        $input = new ProvisionMonetizationAccountInput($accountIdentifier);

        $existingAccount = new MonetizationAccount(
            $monetizationAccountIdentifier,
            $accountIdentifier,
            [],
            null,
            null,
        );

        $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $repository->shouldReceive('findByAccountIdentifier')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($existingAccount);
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(MonetizationAccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->expectException(MonetizationAccountAlreadyExistsException::class);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(ProvisionMonetizationAccountInterface::class);
        $useCase->process($input);
    }
}
