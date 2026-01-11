<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\CountryCode;

class TaxDocumentPolicyService implements TaxDocumentPolicyServiceInterface
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
    ): TaxDocument {
        if ($sellerCountry === CountryCode::JAPAN) {
            if (! $sellerRegistered || ! $qualifiedInvoiceRequired) {
                $deadline = $issueDeadline ?? new DateTimeImmutable();

                return new TaxDocument(
                    TaxDocumentType::SIMPLE_RECEIPT,
                    $sellerCountry,
                    null,
                    $deadline,
                    $reason
                );
            }
            if ($registrationNumber === null || trim($registrationNumber) === '') {
                throw new InvalidArgumentException('Registration number is required for qualified invoice in Japan.');
            }
            $this->assertDeadline($issueDeadline);

            return new TaxDocument(
                TaxDocumentType::JP_QUALIFIED_INVOICE,
                $sellerCountry,
                $registrationNumber,
                $issueDeadline,
                $reason
            );
        }

        if ($sellerCountry === CountryCode::KOREA_REPUBLIC) {
            if ($sellerRegistered && $buyerCountry === CountryCode::KOREA_REPUBLIC) {
                if ($registrationNumber === null || trim($registrationNumber) === '') {
                    throw new InvalidArgumentException('Registration number is required for Korean electronic tax invoice.');
                }
                $this->assertDeadline($issueDeadline);

                return new TaxDocument(
                    TaxDocumentType::KR_ELECTRONIC_TAX_INVOICE,
                    $sellerCountry,
                    $registrationNumber,
                    $issueDeadline,
                    $reason
                );
            }

            if ($buyerIsBusiness) {
                $this->assertDeadline($issueDeadline);

                return new TaxDocument(
                    TaxDocumentType::REVERSE_CHARGE_NOTICE,
                    $sellerCountry,
                    null,
                    $issueDeadline,
                    $reason ?? 'Reverse charge because seller is not VAT registered.'
                );
            }

            $type = $paidByCard
                ? TaxDocumentType::CARD_RECEIPT
                : TaxDocumentType::CASH_RECEIPT;
            $deadline = $issueDeadline ?? new DateTimeImmutable();

            return new TaxDocument(
                $type,
                $sellerCountry,
                null,
                $deadline,
                $reason
            );
        }

        $deadline = $issueDeadline ?? new DateTimeImmutable();

        return new TaxDocument(
            TaxDocumentType::SIMPLE_RECEIPT,
            $sellerCountry,
            null,
            $deadline,
            $reason
        );
    }

    private function assertDeadline(?DateTimeImmutable $deadline): void
    {
        if ($deadline === null) {
            throw new InvalidArgumentException('Issue deadline is required for this tax document.');
        }
    }
}
