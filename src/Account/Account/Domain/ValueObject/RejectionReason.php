<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;

class RejectionReason
{
    public function __construct(
        private readonly RejectionReasonCode $code,
        private readonly ?string $detail = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->code->requiresDetail() && ($this->detail === null || trim($this->detail) === '')) {
            throw new InvalidArgumentException(
                'Detail is required when rejection reason code is OTHER.'
            );
        }

        if ($this->detail !== null && strlen($this->detail) > 1000) {
            throw new InvalidArgumentException(
                'Rejection reason detail cannot exceed 1000 characters.'
            );
        }
    }

    public function code(): RejectionReasonCode
    {
        return $this->code;
    }

    public function detail(): ?string
    {
        return $this->detail;
    }

    /**
     * @return array{code: string, detail: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'detail' => $this->detail,
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: RejectionReasonCode::from(TypedValue::string($data['code'])),
            detail: TypedValue::nullableString($data['detail'] ?? null),
        );
    }
}
