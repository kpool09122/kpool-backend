<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Factory\PayoutAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PayoutAccountRepositoryInterface;
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
        $monetizationAccount = $this->monetizationAccountRepository->findByConnectedAccountId($input->connectedAccountId());

        if ($monetizationAccount === null) {
            throw new MonetizationAccountNotFoundException($input->connectedAccountId());
        }

        $existing = $this->payoutAccountRepository->findByExternalAccountId($input->externalAccountId());

        if ($input->eventType() === self::EVENT_DELETED) {
            if ($existing !== null) {
                $existing->deactivate();
                $this->payoutAccountRepository->save($existing);
            }

            return;
        }

        $payoutAccount = $existing ?? $this->payoutAccountFactory->create(
            $monetizationAccount->monetizationAccountIdentifier(),
            $input->externalAccountId(),
        );

        $meta = new PayoutAccountMeta(
            bankName: $input->bankName(),
            last4: $input->last4(),
            country: $input->country(),
            currency: $input->currency(),
            accountHolderType: $input->accountHolderType(),
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
