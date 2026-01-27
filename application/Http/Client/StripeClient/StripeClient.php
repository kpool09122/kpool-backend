<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient;

use Application\Http\Client\StripeClient\CapturePaymentIntent\CapturePaymentIntentRequest;
use Application\Http\Client\StripeClient\CapturePaymentIntent\CapturePaymentIntentResponse;
use Application\Http\Client\StripeClient\CreateAccountLink\CreateAccountLinkRequest;
use Application\Http\Client\StripeClient\CreateAccountLink\CreateAccountLinkResponse;
use Application\Http\Client\StripeClient\CreateConnectedAccount\CreateConnectedAccountRequest;
use Application\Http\Client\StripeClient\CreateConnectedAccount\CreateConnectedAccountResponse;
use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentRequest;
use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentResponse;
use Application\Http\Client\StripeClient\CreateRefund\CreateRefundRequest;
use Application\Http\Client\StripeClient\CreateRefund\CreateRefundResponse;
use Application\Http\Client\StripeClient\CreateTransfer\CreateTransferRequest;
use Application\Http\Client\StripeClient\CreateTransfer\CreateTransferResponse;
use Application\Http\Client\StripeClient\RetrieveAccount\RetrieveAccountRequest;
use Application\Http\Client\StripeClient\RetrieveAccount\RetrieveAccountResponse;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient as BaseStripeClient;

class StripeClient
{
    private ?BaseStripeClient $client = null;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $apiVersion = '2024-12-18.acacia',
    ) {
    }

    /**
     * @throws ApiErrorException
     */
    public function createTransfer(CreateTransferRequest $request): CreateTransferResponse
    {
        $transfer = $this->client()->transfers->create([
            'amount' => $request->amount(),
            'currency' => $request->currency(),
            'destination' => $request->destination(),
            'metadata' => $request->metadata(),
        ]);

        return new CreateTransferResponse(
            id: $transfer->id,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function createConnectedAccount(CreateConnectedAccountRequest $request): CreateConnectedAccountResponse
    {
        $account = $this->client()->accounts->create([
            'type' => 'express',
            'country' => $request->country(),
            'email' => $request->email(),
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        return new CreateConnectedAccountResponse(
            id: $account->id,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function createAccountLink(CreateAccountLinkRequest $request): CreateAccountLinkResponse
    {
        $accountLink = $this->client()->accountLinks->create([
            'account' => $request->accountId(),
            'refresh_url' => $request->refreshUrl(),
            'return_url' => $request->returnUrl(),
            'type' => 'account_onboarding',
        ]);

        return new CreateAccountLinkResponse(
            url: $accountLink->url,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveAccount(RetrieveAccountRequest $request): RetrieveAccountResponse
    {
        $account = $this->client()->accounts->retrieve($request->accountId());

        return new RetrieveAccountResponse(
            detailsSubmitted: $account->details_submitted ?? false,
            disabledReason: $account->requirements?->disabled_reason,
            chargesEnabled: $account->charges_enabled ?? false,
            payoutsEnabled: $account->payouts_enabled ?? false,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(CreatePaymentIntentRequest $request): CreatePaymentIntentResponse
    {
        $paymentIntent = $this->client()->paymentIntents->create([
            'amount' => $request->amount(),
            'currency' => $request->currency(),
            'customer' => $request->customerId(),
            'payment_method' => $request->paymentMethodId(),
            'payment_method_types' => $request->paymentMethodTypes(),
            'capture_method' => 'manual',
            'confirm' => true,
            'off_session' => true,
            'metadata' => $request->metadata(),
        ]);

        return new CreatePaymentIntentResponse(
            id: $paymentIntent->id,
            status: $paymentIntent->status,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function capturePaymentIntent(CapturePaymentIntentRequest $request): CapturePaymentIntentResponse
    {
        $paymentIntent = $this->client()->paymentIntents->capture(
            $request->paymentIntentId(),
            [
                'amount_to_capture' => $request->amountToCapture(),
            ]
        );

        return new CapturePaymentIntentResponse(
            id: $paymentIntent->id,
            status: $paymentIntent->status,
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function createRefund(CreateRefundRequest $request): CreateRefundResponse
    {
        $refund = $this->client()->refunds->create([
            'payment_intent' => $request->paymentIntentId(),
            'amount' => $request->amount(),
            'reason' => $request->reason(),
            'metadata' => $request->metadata(),
        ]);

        return new CreateRefundResponse(
            id: $refund->id,
            status: $refund->status,
        );
    }

    private function client(): BaseStripeClient
    {
        if ($this->client === null) {
            $this->client = new BaseStripeClient([
                'api_key' => $this->secretKey,
                'stripe_version' => $this->apiVersion,
            ]);
        }

        return $this->client;
    }
}
