<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Settlement\Domain\Exception\TransferGatewayException;
use Source\Monetization\Settlement\Domain\Exception\TransferNotFoundException;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\Service\TransferGatewayInterface;

readonly class ExecuteTransfer implements ExecuteTransferInterface
{
    public function __construct(
        private TransferRepositoryInterface $transferRepository,
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private TransferGatewayInterface $transferGateway,
    ) {
    }

    /**
     * @param ExecuteTransferInputPort $input
     * @return void
     * @throws TransferNotFoundException
     * @throws MonetizationAccountNotFoundException
     */
    public function process(ExecuteTransferInputPort $input): void
    {
        $transfer = $this->transferRepository->findById($input->transferIdentifier());

        if ($transfer === null) {
            throw new TransferNotFoundException($input->transferIdentifier());
        }

        $monetizationAccount = $this->monetizationAccountRepository->findById(
            $transfer->monetizationAccountIdentifier()
        );

        if ($monetizationAccount === null) {
            throw new MonetizationAccountNotFoundException($transfer->monetizationAccountIdentifier());
        }

        try {
            $monetizationAccount->assertCanReceivePayout();

            $stripeTransferId = $this->transferGateway->execute($transfer, $monetizationAccount);

            $transfer->recordStripeTransferId($stripeTransferId);
            $transfer->markSent(new DateTimeImmutable());
        } catch (DomainException|TransferGatewayException $e) {
            $transfer->markFailed($e->getMessage(), new DateTimeImmutable());
        }

        $this->transferRepository->save($transfer);
    }
}
