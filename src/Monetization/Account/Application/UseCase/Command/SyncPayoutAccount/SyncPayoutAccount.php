<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Factory\PayoutAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PayoutAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;

readonly class SyncPayoutAccount implements SyncPayoutAccountInterface
{
    private const string EVENT_DELETED = 'account.external_account.deleted';

    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PayoutAccountRepositoryInterface $payoutAccountRepository,
        private PayoutAccountFactoryInterface $payoutAccountFactory,
    ) {
    }

    /**
     * @throws MonetizationAccountNotFoundException
     */
    public function process(SyncPayoutAccountInputPort $input): void
    {
        $connectedAccountId = new ConnectedAccountId($input->connectedAccountId());
        $monetizationAccount = $this->monetizationAccountRepository->findByConnectedAccountId($connectedAccountId);

        if ($monetizationAccount === null) {
            throw new MonetizationAccountNotFoundException(
                new MonetizationAccountIdentifier($input->connectedAccountId())
            );
        }

        $externalAccountId = new ExternalAccountId($input->externalAccountId());
        $existing = $this->payoutAccountRepository->findByExternalAccountId($externalAccountId);

        if ($input->eventType() === self::EVENT_DELETED) {
            if ($existing !== null) {
                $existing->deactivate();
                $this->payoutAccountRepository->save($existing);
            }

            return;
        }

        $payoutAccount = $existing ?? $this->payoutAccountFactory->create(
            $monetizationAccount->monetizationAccountIdentifier(),
            $externalAccountId,
        );

        $meta = new PayoutAccountMeta(
            bankName: $input->bankName(),
            last4: $input->last4(),
            country: $input->country(),
            currency: $input->currency(),
            accountHolderType: $input->accountHolderType() !== null
                ? AccountHolderType::from($input->accountHolderType())
                : null,
        );
        $payoutAccount->updateMeta($meta);

        if ($input->isDefault()) {
            $payoutAccount->markAsDefault();
        }

        $default = $this->payoutAccountRepository->findDefaultByMonetizationAccountId(
            $monetizationAccount->monetizationAccountIdentifier()
        );

        if ($default === null) {
            $payoutAccount->markAsDefault();
        }

        $payoutAccount->activate();
        $this->payoutAccountRepository->save($payoutAccount);
    }
}
