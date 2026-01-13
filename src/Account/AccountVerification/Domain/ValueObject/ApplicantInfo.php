<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;

readonly class ApplicantInfo
{
    private const int MAX_NAME_LENGTH = 255;

    public function __construct(
        private string  $fullName,
        private ?string $companyName = null,
        private ?string $representativeName = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (trim($this->fullName) === '') {
            throw new InvalidArgumentException('Full name cannot be empty.');
        }

        if (strlen($this->fullName) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException('Full name cannot exceed ' . self::MAX_NAME_LENGTH . ' characters.');
        }

        if ($this->companyName !== null && strlen($this->companyName) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException('Company name cannot exceed ' . self::MAX_NAME_LENGTH . ' characters.');
        }

        if ($this->representativeName !== null && strlen($this->representativeName) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException('Representative name cannot exceed ' . self::MAX_NAME_LENGTH . ' characters.');
        }
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function representativeName(): ?string
    {
        return $this->representativeName;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'company_name' => $this->companyName,
            'representative_name' => $this->representativeName,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            companyName: $data['company_name'] ?? null,
            representativeName: $data['representative_name'] ?? null,
        );
    }
}
