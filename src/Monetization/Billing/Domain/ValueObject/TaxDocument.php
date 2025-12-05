<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Account\Domain\ValueObject\CountryCode;

readonly class TaxDocument
{
    public function __construct(
        private TaxDocumentType $type,
        private CountryCode $country,
        private ?string $registrationNumber,
        private DateTimeImmutable $issueDeadline,
        private ?string $reason = null,
    ) {
        $this->assertRegistration($registrationNumber);
        $this->assertReason($type, $reason);
    }

    public function type(): TaxDocumentType
    {
        return $this->type;
    }

    public function country(): CountryCode
    {
        return $this->country;
    }

    public function registrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function issueDeadline(): DateTimeImmutable
    {
        return $this->issueDeadline;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    private function assertRegistration(?string $registrationNumber): void
    {
        if ($registrationNumber === null) {
            return;
        }
        if (trim($registrationNumber) === '') {
            throw new InvalidArgumentException('Registration number must not be empty when provided.');
        }
    }

    private function assertReason(TaxDocumentType $type, ?string $reason): void
    {
        if ($type === TaxDocumentType::REVERSE_CHARGE_NOTICE && $reason === null) {
            throw new InvalidArgumentException('Reason is required for reverse charge notice.');
        }

        if ($reason !== null && trim($reason) === '') {
            throw new InvalidArgumentException('Reason must not be empty when provided.');
        }
    }
}
