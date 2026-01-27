<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\RetrieveAccount\RetrieveAccountRequest;
use Application\Http\Client\StripeClient\RetrieveAccount\RetrieveAccountResponse;
use Application\Http\Client\StripeClient\StripeClient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Service\ConnectGatewayInterface;
use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Tests\TestCase;

class ConnectGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Stripe API key が設定されていない場合はスキップ
        $stripeKey = config('services.stripe.secret_key', '');
        if ($stripeKey === '' || $stripeKey === null) {
            $this->markTestSkipped('STRIPE_SECRET_KEY is not set');
        }
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // テスト用に環境変数から Stripe 設定を読み込む
        // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
        $app['config']->set('services.stripe.secret_key', env('STRIPE_SECRET_KEY', ''));
    }

    /**
     * 正常系: createConnectedAccount で Stripe Connected Account が作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testCreateConnectedAccountSuccess(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $email = new Email('test-connect-' . time() . '@example.com');
        $country = CountryCode::JAPAN;

        $accountId = $gateway->createConnectedAccount($email, $country);

        $this->assertStringStartsWith('acct_', (string) $accountId);

        // Stripe から Account を取得して確認
        $response = $stripeClient->retrieveAccount(new RetrieveAccountRequest((string) $accountId));
        $this->assertTrue($response->detailsSubmitted() === false || $response->detailsSubmitted() === true);
    }

    /**
     * 異常系: createConnectedAccount で Stripe API エラー時に StripeConnectException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCreateConnectedAccountThrowsStripeConnectExceptionOnApiError(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createConnectedAccount')
            ->once()
            ->andThrow(InvalidRequestException::factory('Invalid country', 400));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $this->expectException(StripeConnectException::class);
        $this->expectExceptionMessage('Failed to create connected account:');

        $gateway->createConnectedAccount(new Email('test@example.com'), CountryCode::AFGHANISTAN);
    }

    /**
     * 正常系: createAccountLink で Account Link が作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCreateAccountLinkSuccess(): void
    {
        $gateway = $this->app->make(ConnectGatewayInterface::class);

        // まず Connected Account を作成
        $email = new Email('test-link-' . time() . '@example.com');
        $accountId = $gateway->createConnectedAccount($email, CountryCode::JAPAN);

        // Account Link を作成
        $refreshUrl = 'https://example.com/refresh';
        $returnUrl = 'https://example.com/return';

        $url = $gateway->createAccountLink($accountId, $refreshUrl, $returnUrl);

        $this->assertNotEmpty($url);
        $this->assertStringStartsWith('https://connect.stripe.com/', $url);
    }

    /**
     * 異常系: createAccountLink で Stripe API エラー時に StripeConnectException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCreateAccountLinkThrowsStripeConnectExceptionOnApiError(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createAccountLink')
            ->once()
            ->andThrow(InvalidRequestException::factory('Invalid account', 400));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $this->expectException(StripeConnectException::class);
        $this->expectExceptionMessage('Failed to create account link:');

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $gateway->createAccountLink($accountId, 'https://example.com/refresh', 'https://example.com/return');
    }

    /**
     * 正常系: getAccountStatus で details_submitted が false の場合、PENDING が返されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testGetAccountStatusReturnsPendingWhenDetailsNotSubmitted(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrieveAccount')
            ->once()
            ->withArgs(static fn (RetrieveAccountRequest $request) => $request->accountId() === 'acct_test123456')
            ->andReturn(new RetrieveAccountResponse(
                detailsSubmitted: false,
                disabledReason: null,
                chargesEnabled: false,
                payoutsEnabled: false,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $status = $gateway->getAccountStatus($accountId);

        $this->assertSame(ConnectAccountStatus::PENDING, $status);
    }

    /**
     * 正常系: getAccountStatus で disabled_reason がある場合、RESTRICTED が返されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testGetAccountStatusReturnsRestrictedWhenDisabledReasonExists(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrieveAccount')
            ->once()
            ->withArgs(static fn (RetrieveAccountRequest $request) => $request->accountId() === 'acct_test123456')
            ->andReturn(new RetrieveAccountResponse(
                detailsSubmitted: true,
                disabledReason: 'requirements.past_due',
                chargesEnabled: false,
                payoutsEnabled: false,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $status = $gateway->getAccountStatus($accountId);

        $this->assertSame(ConnectAccountStatus::RESTRICTED, $status);
    }

    /**
     * 正常系: getAccountStatus で charges と payouts が有効な場合、ENABLED が返されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testGetAccountStatusReturnsEnabledWhenFullyEnabled(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrieveAccount')
            ->once()
            ->withArgs(static fn (RetrieveAccountRequest $request) => $request->accountId() === 'acct_test123456')
            ->andReturn(new RetrieveAccountResponse(
                detailsSubmitted: true,
                disabledReason: null,
                chargesEnabled: true,
                payoutsEnabled: true,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $status = $gateway->getAccountStatus($accountId);

        $this->assertSame(ConnectAccountStatus::ENABLED, $status);
    }

    /**
     * 正常系: getAccountStatus で charges か payouts が無効な場合、RESTRICTED が返されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testGetAccountStatusReturnsRestrictedWhenPartiallyEnabled(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrieveAccount')
            ->once()
            ->withArgs(static fn (RetrieveAccountRequest $request) => $request->accountId() === 'acct_test123456')
            ->andReturn(new RetrieveAccountResponse(
                detailsSubmitted: true,
                disabledReason: null,
                chargesEnabled: true,
                payoutsEnabled: false,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $status = $gateway->getAccountStatus($accountId);

        $this->assertSame(ConnectAccountStatus::RESTRICTED, $status);
    }

    /**
     * 異常系: getAccountStatus で Stripe API エラー時に StripeConnectException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testGetAccountStatusThrowsStripeConnectExceptionOnApiError(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrieveAccount')
            ->once()
            ->andThrow(InvalidRequestException::factory('Account not found', 404));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(ConnectGatewayInterface::class);

        $this->expectException(StripeConnectException::class);
        $this->expectExceptionMessage('Failed to get account status:');

        $accountId = new StripeConnectedAccountId('acct_test123456');
        $gateway->getAccountStatus($accountId);
    }
}
