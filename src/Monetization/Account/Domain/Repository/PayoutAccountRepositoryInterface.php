<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;

interface PayoutAccountRepositoryInterface
{
    public function findById(PayoutAccountIdentifier $identifier): ?PayoutAccount;

    public function findByExternalAccountId(ExternalAccountId $externalAccountId): ?PayoutAccount;

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PayoutAccount;

    /**
     * @return PayoutAccount[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array;

    public function save(PayoutAccount $payoutAccount): void;
}
