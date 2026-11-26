<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;

interface PaymentMethodArchiveFactoryInterface
{
    public function create(PaymentMethod $paymentMethod, DateTimeImmutable $archivedAt): PaymentMethodArchive;
}
