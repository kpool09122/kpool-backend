<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Service;

use Application\Http\Client\StripeClient\CreateTransfer\CreateTransferRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Service\TransferGatewayInterface;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;
use Source\Monetization\Settlement\Infrastructure\Exception\StripeTransferException;
use Stripe\Exception\ApiErrorException;

readonly class TransferGateway implements TransferGatewayInterface
{
    public function __construct(
        private StripeClient $stripeClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws StripeTransferException
     */
    public function execute(Transfer $transfer, MonetizationAccount $account): StripeTransferId
    {
        try {
            $request = new CreateTransferRequest(
                amount: $transfer->amount()->amount(),
                currency: strtolower($transfer->amount()->currency()->value),
                destination: (string) $account->stripeConnectedAccountId(),
                metadata: [
                    'transfer_id' => (string) $transfer->transferIdentifier(),
                    'settlement_batch_id' => (string) $transfer->settlementBatchIdentifier(),
                ],
            );

            $response = $this->stripeClient->createTransfer($request);

            $this->logger->info('Transfer executed successfully', [
                'transfer_id' => (string) $transfer->transferIdentifier(),
                'stripe_transfer_id' => $response->id(),
                'amount' => $transfer->amount()->amount(),
                'currency' => $transfer->amount()->currency()->value,
            ]);

            return new StripeTransferId($response->id());
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error during transfer', [
                'transfer_id' => (string) $transfer->transferIdentifier(),
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw new StripeTransferException(
                sprintf('Transfer failed: %s', $e->getMessage()),
                $e
            );
        }
    }
}
