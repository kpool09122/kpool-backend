<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;

interface PaymentMethodArchiveRepositoryInterface
{
    public function save(PaymentMethodArchive $archive): void;

    public function findByPaymentMethodIdentifier(PaymentMethodIdentifier $identifier): ?PaymentMethodArchive;
}
