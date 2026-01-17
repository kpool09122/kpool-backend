<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerInput;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerInterface;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Service\ConnectGatewayInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OnboardSellerTest extends TestCase
{
    /**
     * 正常系: 新規でStripe Connected Accountを作成しオンボーディングURLを返却すること
     *
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     */
    public function testProcessCreatesConnectedAccountAndReturnsOnboardingUrl(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = new StripeConnectedAccountId('acct_1234567890');
        $expectedUrl = 'https://connect.stripe.com/setup/onboarding/1234';
        $email = new Email('seller@example.com');
        $countryCode = CountryCode::JAPAN;

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [],
            null,
            null
        );

        $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($account);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static function (MonetizationAccount $savedAccount) use ($stripeConnectedAccountId) {
                return $savedAccount->stripeConnectedAccountId() === $stripeConnectedAccountId
                    && $savedAccount->hasCapability(Capability::SELL)
                    && $savedAccount->hasCapability(Capability::RECEIVE_PAYOUT);
            }))
            ->andReturnNull();

        $connectGateway = Mockery::mock(ConnectGatewayInterface::class);
        $connectGateway->shouldReceive('createConnectedAccount')
            ->once()
            ->with($email, $countryCode)
            ->andReturn($stripeConnectedAccountId);

        $connectGateway->shouldReceive('createAccountLink')
            ->once()
            ->with(
                $stripeConnectedAccountId,
                'https://example.com/refresh',
                'https://example.com/return'
            )
            ->andReturn($expectedUrl);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $repository);
        $this->app->instance(ConnectGatewayInterface::class, $connectGateway);
        $useCase = $this->app->make(OnboardSellerInterface::class);

        $input = new OnboardSellerInput(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $email,
            $countryCode,
            'https://example.com/refresh',
            'https://example.com/return'
        );

        $result = $useCase->process($input);

        $this->assertSame($expectedUrl, $result);
    }

    /**
     * 正常系: 既にStripe Connected Accountがある場合はAccount Linkのみを返却すること
     *
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     */
    public function testProcessReturnsOnboardingUrlWhenConnectedAccountExists(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $existingStripeConnectedAccountId = new StripeConnectedAccountId('acct_existing');
        $expectedUrl = 'https://connect.stripe.com/setup/onboarding/existing';

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [Capability::SELL, Capability::RECEIVE_PAYOUT],
            null,
            $existingStripeConnectedAccountId
        );

        $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($account);
        $repository->shouldNotReceive('save');

        $connectGateway = Mockery::mock(ConnectGatewayInterface::class);
        $connectGateway->shouldNotReceive('createConnectedAccount');
        $connectGateway->shouldReceive('createAccountLink')
            ->once()
            ->with(
                $existingStripeConnectedAccountId,
                'https://example.com/refresh',
                'https://example.com/return'
            )
            ->andReturn($expectedUrl);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $repository);
        $this->app->instance(ConnectGatewayInterface::class, $connectGateway);
        $useCase = $this->app->make(OnboardSellerInterface::class);

        $input = new OnboardSellerInput(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Email('seller@example.com'),
            CountryCode::JAPAN,
            'https://example.com/refresh',
            'https://example.com/return'
        );

        $result = $useCase->process($input);

        $this->assertSame($expectedUrl, $result);
    }

    /**
     * 異常系: MonetizationAccountが存在しない場合に例外がスローされること
     *
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     */
    public function testProcessThrowsExceptionWhenAccountNotFound(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();

        $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->andReturn(null);
        $repository->shouldNotReceive('save');

        $connectGateway = Mockery::mock(ConnectGatewayInterface::class);
        $connectGateway->shouldNotReceive('createConnectedAccount');
        $connectGateway->shouldNotReceive('createAccountLink');

        $this->expectException(MonetizationAccountNotFoundException::class);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $repository);
        $this->app->instance(ConnectGatewayInterface::class, $connectGateway);
        $useCase = $this->app->make(OnboardSellerInterface::class);

        $input = new OnboardSellerInput(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Email('seller@example.com'),
            CountryCode::JAPAN,
            'https://example.com/refresh',
            'https://example.com/return'
        );

        $useCase->process($input);
    }
}
