<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Service;

use DateTimeImmutable;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;

interface TaxDocumentPolicyServiceInterface
{
    public function decide(
        CountryCode $sellerCountry,
        bool $sellerRegistered,
        bool $qualifiedInvoiceRequired,
        CountryCode $buyerCountry,
        bool $buyerIsBusiness,
        bool $paidByCard,
        ?string $registrationNumber,
        ?DateTimeImmutable $issueDeadline,
        ?string $reason = null,
    ): TaxDocument;
}
