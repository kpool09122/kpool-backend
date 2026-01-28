<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Infrastructure\Service;

use Application\Http\Client\StripeClient\CapturePaymentIntent\CapturePaymentIntentRequest;
use Application\Http\Client\StripeClient\CapturePaymentIntent\CapturePaymentIntentResponse;
use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentRequest;
use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentResponse;
use Application\Http\Client\StripeClient\CreateRefund\CreateRefundRequest;
use Application\Http\Client\StripeClient\CreateRefund\CreateRefundResponse;
use Application\Http\Client\StripeClient\StripeClient;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Infrastructure\Exception\StripeApiException;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePayment;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    /**
     * 正常系: authorize で PaymentIntent が作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntent(): void
    {
        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        $stripeCustomerId = 'cus_test_' . time();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => $stripeCustomerId,
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_card_visa';
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        // StripeClientをモック
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(fn (CreatePaymentIntentRequest $request) => $request->amount() === 1000
                && $request->currency() === 'jpy'
                && $request->customerId() === $stripeCustomerId
                && $request->paymentMethodId() === $stripePaymentMethodId)
            ->andReturn(new CreatePaymentIntentResponse(
                id: 'pi_test123456',
                status: PaymentIntent::STATUS_REQUIRES_CAPTURE,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGatewayInterface::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertSame('pi_test123456', $stripePaymentIntentId);
    }

    /**
     * 正常系: authorize 後に capture が成功すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testCaptureSucceedsAfterAuthorize(): void
    {
        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 500,
            'stripe_payment_method_id' => 'pm_card_visa',
            'stripe_payment_intent_id' => 'pi_test_capture',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 500, Currency::JPY);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('capturePaymentIntent')
            ->once()
            ->withArgs(static fn (CapturePaymentIntentRequest $request) => $request->paymentIntentId() === 'pi_test_capture'
                && $request->amountToCapture() === 500)
            ->andReturn(new CapturePaymentIntentResponse(
                id: 'pi_test_capture',
                status: PaymentIntent::STATUS_SUCCEEDED,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);
        $gateway->capture($payment);
    }

    /**
     * 正常系: capture 後に refund が成功すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testRefundSucceedsAfterCapture(): void
    {
        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test123456',
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 2000,
            'stripe_payment_method_id' => 'pm_card_visa',
            'stripe_payment_intent_id' => 'pi_test_refund',
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 2000, Currency::JPY);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createRefund')
            ->once()
            ->withArgs(static fn (CreateRefundRequest $request) => $request->paymentIntentId() === 'pi_test_refund'
                && $request->amount() === 1000
                && $request->reason() === 'requested_by_customer')
            ->andReturn(new CreateRefundResponse(
                id: 're_test123456',
                status: 'succeeded',
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);
        $gateway->refund($payment, new Money(1000, Currency::JPY), 'Customer request');
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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(CardException::factory('Card declined', 402, null, null, null, 'card_declined'));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn(new CreatePaymentIntentResponse(
                id: 'pi_test123456',
                status: 'requires_payment_method',
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Authorization failed: unexpected status "requires_payment_method"');

        $gateway->authorize($payment);
    }

    /**
     * 正常系: authorize で PaymentIntent が作成されること（KRW通貨）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntentWithKrw(): void
    {
        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test_krw',
        ]);

        // Payment レコードを作成
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 10000,
            'currency' => 'KRW',
            'stripe_payment_method_id' => 'pm_card_visa',
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 10000, Currency::KRW);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(static fn (CreatePaymentIntentRequest $request) => $request->amount() === 10000
                && $request->currency() === 'krw')
            ->andReturn(new CreatePaymentIntentResponse(
                id: 'pi_test_krw',
                status: PaymentIntent::STATUS_REQUIRES_CAPTURE,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGatewayInterface::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertSame('pi_test_krw', $stripePaymentIntentId);
    }

    /**
     * 正常系: authorize で PaymentIntent が作成されること（USD通貨）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeCreatesPaymentIntentWithUsd(): void
    {
        // Account と MonetizationAccount を作成
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'stripe_customer_id' => 'cus_test_usd',
        ]);

        // Payment レコードを作成（USD は cents なので 1000 = $10.00）
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'currency' => 'USD',
            'stripe_payment_method_id' => 'pm_card_visa',
        ]);

        // ドメインエンティティを作成
        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::USD);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(static fn (CreatePaymentIntentRequest $request) => $request->amount() === 1000
                && $request->currency() === 'usd')
            ->andReturn(new CreatePaymentIntentResponse(
                id: 'pi_test_usd',
                status: PaymentIntent::STATUS_REQUIRES_CAPTURE,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app->make(PaymentGatewayInterface::class);
        $gateway->authorize($payment);

        // DB に stripe_payment_intent_id が保存されていることを確認
        $stripePaymentIntentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');
        $this->assertSame('pi_test_usd', $stripePaymentIntentId);
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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('capturePaymentIntent')
            ->once()
            ->andReturn(new CapturePaymentIntentResponse(
                id: 'pi_test123456',
                status: 'requires_payment_method',
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createRefund')
            ->once()
            ->andReturn(new CreateRefundResponse(
                id: 're_test123456',
                status: 'failed',
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('capturePaymentIntent')
            ->once()
            ->andThrow(CardException::factory('Capture failed', 402, null, null, null, 'capture_failed'));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createRefund')
            ->once()
            ->andThrow(CardException::factory('Refund failed', 402, null, null, null, 'refund_failed'));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(PaymentGatewayInterface::class);

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
