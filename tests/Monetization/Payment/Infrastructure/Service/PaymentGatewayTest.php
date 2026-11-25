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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentOutput;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Monetization\Shared\Exception\InvalidPaymentMethodException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePayment;
use Tests\Helper\CreatePaymentMethod;
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => $stripePaymentMethodId]);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'payment_method_id' => $methodId,
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app()->make(PaymentGatewayInterface::class);
        $stripePaymentIntentId = $gateway->authorize($payment, $this->createPaymentMethod($payment, $stripePaymentMethodId));

        $this->assertNull(DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id'));
        $this->assertSame('pi_test123456', (string) $stripePaymentIntentId);
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_card_visa']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 500,
            'payment_method_id' => $methodId,
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_card_visa']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 2000,
            'payment_method_id' => $methodId,
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_test1234567890']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'payment_method_id' => $methodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Stripe customer not linked to monetization account.');

        $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_test1234567890'));
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

        $methodId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'payment_method_id' => $methodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Monetization account not found.');

        $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_test1234567890'));
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_invalid']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'payment_method_id' => $methodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(CardException::factory('Card declined', 402, null, null, null, 'card_declined'));

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(ApiException::class);

        $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_invalid'));
    }

    /**
     * 異常系: Payment レコードが存在しない場合、PaymentGatewayException がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testAuthorizeUseCaseSavesPaymentWithIntentIdAfterGateway(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId, ['stripe_customer_id' => 'cus_test123456']);
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_card_visa', 'brand' => 'visa', 'last4' => '4242']);
        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->shouldReceive('createPaymentIntent')->once()
            ->withArgs(function (CreatePaymentIntentRequest $request) use ($methodId): bool {
                $this->assertDatabaseMissing('payments', ['payment_method_id' => $methodId]);

                return $request->paymentMethodId() === 'pm_card_visa';
            })->andReturn(new CreatePaymentIntentResponse('pi_new_payment', PaymentIntent::STATUS_REQUIRES_CAPTURE));
        $this->app()->instance(StripeClient::class, $stripeClient);
        $input = new AuthorizePaymentInput(new OrderIdentifier(StrTestHelper::generateUuid()), new MonetizationAccountIdentifier($monetizationAccountId), new Money(1000, Currency::JPY), new PaymentMethodIdentifier($methodId));
        $output = new AuthorizePaymentOutput();
        $this->app()->make(AuthorizePaymentInterface::class)->process($input, $output);
        $this->assertDatabaseHas('payments', ['id' => $output->toArray()['paymentId'], 'status' => 'authorized', 'stripe_payment_intent_id' => 'pi_new_payment', 'payment_method_id' => $methodId]);
        $this->assertSame('visa', $output->toArray()['paymentMethodBrand']);
        $this->assertSame('4242', $output->toArray()['paymentMethodLast4']);
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_test1234567890']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'payment_method_id' => $methodId,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn(new CreatePaymentIntentResponse(
                id: 'pi_test123456',
                status: 'requires_payment_method',
            ));

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Authorization failed: unexpected status "requires_payment_method"');

        $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_test1234567890'));
    }

    #[Group('useDb')]
    public function testAuthorizeUseCaseDoesNotSavePaymentWhenGatewayRejectsAuthorization(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($accountId, ['stripe_customer_id' => 'cus_test123456']);
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $accountId]);
        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->shouldReceive('createPaymentIntent')->once()
            ->andReturn(new CreatePaymentIntentResponse('pi_rejected_payment', 'requires_payment_method'));
        $this->app()->instance(StripeClient::class, $stripeClient);
        $input = new AuthorizePaymentInput(new OrderIdentifier(StrTestHelper::generateUuid()), new MonetizationAccountIdentifier($accountId), new Money(1000, Currency::JPY), new PaymentMethodIdentifier($methodId));

        try {
            $this->app()->make(AuthorizePaymentInterface::class)->process($input, new AuthorizePaymentOutput());
            $this->fail('Rejected authorization must throw.');
        } catch (PaymentGatewayException) {
            $this->assertSame(0, DB::table('payments')->count());
        }
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_card_visa']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 10000,
            'currency' => 'KRW',
            'payment_method_id' => $methodId,
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app()->make(PaymentGatewayInterface::class);
        $stripePaymentIntentId = $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_card_visa'));

        $this->assertNull(DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id'));
        $this->assertSame('pi_test_krw', (string) $stripePaymentIntentId);
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
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $monetizationAccountId, 'external_payment_method_id' => 'pm_card_visa']);
        CreatePayment::create($paymentId, [
            'buyer_monetization_account_id' => $monetizationAccountId,
            'amount' => 1000,
            'currency' => 'USD',
            'payment_method_id' => $methodId,
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        // PaymentGateway を実行
        $gateway = $this->app()->make(PaymentGatewayInterface::class);
        $stripePaymentIntentId = $gateway->authorize($payment, $this->createPaymentMethod($payment, 'pm_card_visa'));

        $this->assertNull(DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id'));
        $this->assertSame('pi_test_usd', (string) $stripePaymentIntentId);
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
            'status' => 'pending',
            'stripe_payment_intent_id' => null,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

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
            'status' => 'pending',
            'stripe_payment_intent_id' => null,
        ]);

        $payment = $this->createPayment($paymentId, $monetizationAccountId, 1000, Currency::JPY);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Refund failed: status "failed"');

        $gateway->refund($payment, new Money(500, Currency::JPY), 'Test refund');
    }

    /**
     * 異常系: Stripe API エラー時に StripeApiException がスローされること (capture).
     *
     * @return void
     * @throws ApiException
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(ApiException::class);

        $gateway->capture($payment);
    }

    /**
     * 異常系: Stripe API エラー時に StripeApiException がスローされること (refund).
     *
     * @return void
     * @throws ApiException
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

        $this->app()->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app()->make(PaymentGatewayInterface::class);

        $this->expectException(ApiException::class);

        $gateway->refund($payment, new Money(500, Currency::JPY), 'Test refund');
    }

    #[Group('useDb')]
    public function testUsesProvidedCardForAuthorization(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($accountId, ['stripe_customer_id' => 'cus_test123456']);
        $methodId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($methodId, ['monetization_account_id' => $accountId, 'external_payment_method_id' => 'pm_1234567890', 'type' => 'card']);
        $paymentId = StrTestHelper::generateUuid();
        CreatePayment::create($paymentId, ['buyer_monetization_account_id' => $accountId, 'payment_method_id' => $methodId]);
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('createPaymentIntent')->once()
            ->withArgs(static fn (CreatePaymentIntentRequest $request): bool => $request->paymentMethodTypes() === ['card'] && $request->paymentMethodId() === 'pm_1234567890')
            ->andReturn(new CreatePaymentIntentResponse('pi_type_test', PaymentIntent::STATUS_REQUIRES_CAPTURE));
        $this->app()->instance(StripeClient::class, $client);
        $payment = $this->createPayment($paymentId, $accountId, 1000, Currency::JPY);
        $intentId = $this->app()->make(PaymentGatewayInterface::class)->authorize($payment, $this->createPaymentMethod($payment, 'pm_1234567890'));
        $this->assertSame('pi_type_test', (string) $intentId);
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'stripe_payment_intent_id' => null]);
    }

    /** @return iterable<string, array{bool, bool, bool}> */
    public static function invalidMethods(): iterable
    {
        yield 'missing' => [false, true, true];
        yield 'foreign account' => [true, false, true];
        yield 'inactive' => [true, true, false];
    }

    #[Group('useDb')]
    #[DataProvider('invalidMethods')]
    public function testAuthorizeRejectsInvalidMethodBeforePersistingOrCallingStripe(bool $exists, bool $owned, bool $active): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($accountId, ['stripe_customer_id' => 'cus_test123456']);
        $methodId = StrTestHelper::generateUuid();
        if ($exists) {
            $methodAccountId = $accountId;
            if (! $owned) {
                $methodAccountId = StrTestHelper::generateUuid();
                CreateMonetizationAccount::create($methodAccountId);
            }
            CreatePaymentMethod::create($methodId, ['monetization_account_id' => $methodAccountId, 'status' => $active ? 'active' : 'inactive']);
        }
        $client = Mockery::mock(StripeClient::class);
        $client->shouldNotReceive('createPaymentIntent');
        $this->app()->instance(StripeClient::class, $client);
        $input = new AuthorizePaymentInput(new OrderIdentifier(StrTestHelper::generateUuid()), new MonetizationAccountIdentifier($accountId), new Money(1000, Currency::JPY), new PaymentMethodIdentifier($methodId));

        try {
            $this->app()->make(AuthorizePaymentInterface::class)->process($input, new AuthorizePaymentOutput());
            $this->fail('Invalid payment method must be rejected.');
        } catch (InvalidPaymentMethodException) {
            $this->assertSame(0, DB::table('payments')->count());
        }
    }

    private function createPaymentMethod(Payment $payment, string $externalPaymentMethodId): PaymentMethod
    {
        return new PaymentMethod(
            $payment->paymentMethodIdentifier(),
            $payment->buyerMonetizationAccountIdentifier(),
            new ExternalPaymentMethodId($externalPaymentMethodId),
            PaymentMethodType::CARD,
        );
    }

    private function createPayment(
        string $paymentId,
        string $monetizationAccountId,
        int $amount,
        Currency $currency,
    ): Payment {
        $intentId = DB::table('payments')->where('id', $paymentId)->value('stripe_payment_intent_id');

        return new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money($amount, $currency),
            new PaymentMethodIdentifier(TypedValue::string(DB::table('payments')->where('id', $paymentId)->value('payment_method_id'))),
            new DateTimeImmutable(),
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, $currency),
            null,
            stripePaymentIntentId: $intentId !== null ? new StripePaymentIntentId(TypedValue::string($intentId)) : null,
        );
    }
}
