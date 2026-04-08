<?php

declare(strict_types=1);

namespace Application\Http\Action\Webhook\Stripe;

use Application\Jobs\SyncPayoutAccountJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Stripe\BankAccount;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

readonly class StripeWebhookAction
{
    private const array SUPPORTED_EVENTS = [
        'account.external_account.created',
        'account.external_account.updated',
        'account.external_account.deleted',
    ];

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        /** @var string $webhookSecret */
        $webhookSecret = config('services.stripe.webhook_secret', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        if (! in_array($event->type, self::SUPPORTED_EVENTS, true)) {
            return response()->json(['status' => 'ignored'], Response::HTTP_OK);
        }

        $connectedAccountId = $event->account;
        /** @var BankAccount $externalAccount */
        $externalAccount = $event->data->object;

        $this->logger->info('Stripe webhook received', [
            'event_type' => $event->type,
            'connected_account_id' => $connectedAccountId,
            'external_account_id' => $externalAccount->id ?? null,
        ]);

        SyncPayoutAccountJob::dispatch(
            connectedAccountId: $connectedAccountId,
            externalAccountId: $externalAccount->id,
            eventType: $event->type,
            bankName: $externalAccount->bank_name ?? null,
            last4: $externalAccount->last4 ?? null,
            country: $externalAccount->country ?? null,
            currency: $externalAccount->currency ?? null,
            accountHolderType: $externalAccount->account_holder_type ?? null,
            isDefault: ($externalAccount->default_for_currency ?? false) === true,
        );

        return response()->json(['status' => 'accepted'], Response::HTTP_OK);
    }
}
