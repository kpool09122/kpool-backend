<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Infrastructure\Service;

use Application\Http\Client\StripeClient;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Infrastructure\Exception\StripeApiException;
use Source\Monetization\Payment\Infrastructure\Service\PaymentGateway;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePayment;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
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
     * 正常系: authorize で PaymentIntent が作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntent(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        // Stripe Customer を作成
        $stripeCustomer = $stripeClient->client()->customers->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // テストトークン pm_card_visa を使用して Customer にアタッチ
        $stripeClient->client()->paymentMethods->attach(
            'pm_card_visa',
            ['customer' => $stripeCustomer->id]
        );
        $stripePaymentMethodId = 'pm_card_visa';

        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGateway::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertNotNull($stripePaymentIntentId);
        $this->assertStringStartsWith('pi_', $stripePaymentIntentId);

        // Stripe から PaymentIntent を取得して状態を確認
        $paymentIntent = $stripeClient->client()->paymentIntents->retrieve($stripePaymentIntentId);
        $this->assertSame('requires_capture', $paymentIntent->status);
        $this->assertSame(1000, $paymentIntent->amount);
        $this->assertSame('jpy', $paymentIntent->currency);
    }

    /**
     * 正常系: authorize 後に capture が成功すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testCaptureSucceedsAfterAuthorize(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        // Stripe Customer を作成
        $stripeCustomer = $stripeClient->client()->customers->create([
            'email' => 'capture-test@example.com',
        ]);

        // テストトークン pm_card_visa を使用して Customer にアタッチ
        $stripeClient->client()->paymentMethods->attach(
            'pm_card_visa',
            ['customer' => $stripeCustomer->id]
        );
        $stripePaymentMethodId = 'pm_card_visa';

        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 500,
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 500, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        // Authorize
        $gateway->authorize($payment);

        // Capture
        $gateway->capture($payment);

        // Stripe から PaymentIntent を取得して状態を確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $paymentIntent = $stripeClient->client()->paymentIntents->retrieve($stripePaymentIntentId);
        $this->assertSame('succeeded', $paymentIntent->status);
    }

    /**
     * 正常系: capture 後に refund が成功すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testRefundSucceedsAfterCapture(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        // Stripe Customer を作成
        $stripeCustomer = $stripeClient->client()->customers->create([
            'email' => 'refund-test@example.com',
        ]);

        // テストトークン pm_card_visa を使用して Customer にアタッチ
        $stripeClient->client()->paymentMethods->attach(
            'pm_card_visa',
            ['customer' => $stripeCustomer->id]
        );
        $stripePaymentMethodId = 'pm_card_visa';

        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 2000,
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 2000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        // Authorize -> Capture -> Refund
        $gateway->authorize($payment);
        $gateway->capture($payment);
        $gateway->refund($payment, new Money(1000, Currency::JPY), 'Customer request');

        // Stripe から PaymentIntent を取得して部分返金を確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $paymentIntent = $stripeClient->client()->paymentIntents->retrieve($stripePaymentIntentId);
        $this->assertSame(2000, $paymentIntent->amount);
        $this->assertSame(2000, $paymentIntent->amount_received);
    }

    /**
     * 異常系: Stripe Customer が設定されていない場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeFailsWithoutStripeCustomer(): void
    {
        // Account と MonetizationAccount を作成（Stripe Customer なし）
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => null,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'stripe_payment_method_id' => 'pm_test',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Stripe customer not linked to monetization account.');

        $gateway->authorize($payment);
    }

    /**
     * 異常系: Stripe Payment Method が設定されていない場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeFailsWithoutStripePaymentMethod(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'stripe_payment_method_id' => null,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Stripe payment method not set.');

        $gateway->authorize($payment);
    }

    /**
     * 異常系: MonetizationAccount が存在しない場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeFailsWhenMonetizationAccountNotFound(): void
    {
        $paymentId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'stripe_payment_method_id' => 'pm_test',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Monetization account not found.');

        $gateway->authorize($payment);
    }

    /**
     * 異常系: Stripe API エラー時に StripeApiException がスローされること (authorize).
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeThrowsStripeApiExceptionOnApiError(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'stripe_payment_method_id' => 'pm_invalid',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // StripeClient をモック
        $mockPaymentIntents = Mockery::mock();
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(CardException::factory('Card declined', 402, null, null, null, 'card_declined'));

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('paymentIntents')
            ->andReturn($mockPaymentIntents);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(StripeApiException::class);

        $gateway->authorize($payment);
    }

    /**
     * 異常系: Payment レコードが存在しない場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeFailsWhenPaymentRecordNotFound(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        // Payment レコードを作成しない
        $paymentId = StrTestHelper::generateUuid();
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Payment record not found in database.');

        $gateway->authorize($payment);
    }

    /**
     * 異常系: PaymentIntent のステータスが requires_capture 以外の場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeFailsWhenUnexpectedPaymentIntentStatus(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'stripe_payment_method_id' => 'pm_test',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // PaymentIntent のモック（status が requires_capture 以外）
        $mockPaymentIntent = (object) [
            'id' => 'pi_test123456',
            'status' => 'requires_payment_method',
        ];

        $mockPaymentIntents = Mockery::mock();
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andReturn($mockPaymentIntent);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('paymentIntents')
            ->andReturn($mockPaymentIntents);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Authorization failed: unexpected status "requires_payment_method"');

        $gateway->authorize($payment);
    }

    /**
     * 正常系: authorize で PaymentIntent が作成されること（KRW通貨）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntentWithKrw(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        // Stripe Customer を作成
        $stripeCustomer = $stripeClient->client()->customers->create([
            'email' => 'test-krw@example.com',
            'name' => 'Test User KRW',
        ]);

        // テストトークン pm_card_visa を使用して Customer にアタッチ
        $stripeClient->client()->paymentMethods->attach(
            'pm_card_visa',
            ['customer' => $stripeCustomer->id]
        );
        $stripePaymentMethodId = 'pm_card_visa';

        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 10000,
            'currency' => 'KRW',
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 10000, Currency::KRW);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGateway::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertNotNull($stripePaymentIntentId);
        $this->assertStringStartsWith('pi_', $stripePaymentIntentId);

        // Stripe から PaymentIntent を取得して状態を確認
        $paymentIntent = $stripeClient->client()->paymentIntents->retrieve($stripePaymentIntentId);
        $this->assertSame('requires_capture', $paymentIntent->status);
        $this->assertSame(10000, $paymentIntent->amount);
        $this->assertSame('krw', $paymentIntent->currency);
    }

    /**
     * 正常系: authorize で PaymentIntent が作成されること（USD通貨）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ApiErrorException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntentWithUsd(): void
    {
        $stripeClient = $this->app->make(StripeClient::class);
        // Stripe Customer を作成
        $stripeCustomer = $stripeClient->client()->customers->create([
            'email' => 'test-usd@example.com',
            'name' => 'Test User USD',
        ]);

        // テストトークン pm_card_visa を使用して Customer にアタッチ
        $stripeClient->client()->paymentMethods->attach(
            'pm_card_visa',
            ['customer' => $stripeCustomer->id]
        );
        $stripePaymentMethodId = 'pm_card_visa';

        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomer->id,
        ]);

        // Payment レコードを作成（USD は cents なので 1000 = $10.00）
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'currency' => 'USD',
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::USD);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGateway::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertNotNull($stripePaymentIntentId);
        $this->assertStringStartsWith('pi_', $stripePaymentIntentId);

        // Stripe から PaymentIntent を取得して状態を確認
        $paymentIntent = $stripeClient->client()->paymentIntents->retrieve($stripePaymentIntentId);
        $this->assertSame('requires_capture', $paymentIntent->status);
        $this->assertSame(1000, $paymentIntent->amount);
        $this->assertSame('usd', $paymentIntent->currency);
    }

    /**
     * 異常系: PaymentIntent ID が存在しない場合、PaymentGatewayException がスローされること (capture).
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCaptureFailsWithoutPaymentIntentId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'authorized',
            'stripe_payment_intent_id' => null,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Stripe Payment Intent not found for this payment.');

        $gateway->capture($payment);
    }

    /**
     * 異常系: PaymentIntent ID が存在しない場合、PaymentGatewayException がスローされること (refund).
     *
     * @return void
     * @throws PaymentGatewayException
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRefundFailsWithoutPaymentIntentId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'captured',
            'stripe_payment_intent_id' => null,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Stripe Payment Intent not found for this payment.');

        $gateway->refund($payment, new Money(500, Currency::JPY), 'Test refund');
    }

    /**
     * 異常系: capture 後の PaymentIntent ステータスが succeeded 以外の場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCaptureFailsWhenUnexpectedPaymentIntentStatus(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'authorized',
            'stripe_payment_intent_id' => 'pi_test123456',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // PaymentIntent のモック（status が succeeded 以外）
        $mockPaymentIntent = (object) [
            'status' => 'requires_payment_method',
        ];

        $mockPaymentIntents = Mockery::mock();
        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->andReturn($mockPaymentIntent);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('paymentIntents')
            ->andReturn($mockPaymentIntents);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Capture failed: unexpected status "requires_payment_method"');

        $gateway->capture($payment);
    }

    /**
     * 異常系: refund のステータスが succeeded/pending 以外の場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRefundFailsWhenUnexpectedRefundStatus(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'captured',
            'stripe_payment_intent_id' => 'pi_test123456',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // Refund のモック（status が succeeded/pending 以外）
        $mockRefund = (object) [
            'status' => 'failed',
        ];

        $mockRefunds = Mockery::mock();
        $mockRefunds->shouldReceive('create')
            ->once()
            ->andReturn($mockRefund);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('refunds')
            ->andReturn($mockRefunds);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Refund failed: status "failed"');

        $gateway->refund($payment, new Money(500, Currency::JPY), 'Test refund');
    }

    /**
     * 異常系: Stripe API エラー時に StripeApiException がスローされること (capture).
     *
     * @return void
     * @throws StripeApiException
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCaptureThrowsStripeApiExceptionOnApiError(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'authorized',
            'stripe_payment_intent_id' => 'pi_test123456',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $mockPaymentIntents = Mockery::mock();
        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->andThrow(CardException::factory('Capture failed', 402, null, null, null, 'capture_failed'));

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('paymentIntents')
            ->andReturn($mockPaymentIntents);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(StripeApiException::class);

        $gateway->capture($payment);
    }

    /**
     * 異常系: Stripe API エラー時に StripeApiException がスローされること (refund).
     *
     * @return void
     * @throws StripeApiException
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRefundThrowsStripeApiExceptionOnApiError(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'status' => 'captured',
            'stripe_payment_intent_id' => 'pi_test123456',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $mockRefunds = Mockery::mock();
        $mockRefunds->shouldReceive('create')
            ->once()
            ->andThrow(CardException::factory('Refund failed', 402, null, null, null, 'refund_failed'));

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('refunds')
            ->andReturn($mockRefunds);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGateway::class);

        $this->expectException(StripeApiException::class);

        $gateway->refund($payment, new Money(500, Currency::JPY), 'Test refund');
    }

    private function createPayment(
        string $paymentId,
        string $monetizationAccountId,
        int $amount,
        Currency $currency,
    ): Payment {
        return new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money($amount, $currency),
            new PaymentMethod(
                new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
                PaymentMethodType::CARD,
                'Visa **** 4242',
                true,
            ),
            new DateTimeImmutable(),
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, $currency),
            null,
        );
    }
}
