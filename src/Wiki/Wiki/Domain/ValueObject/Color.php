<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Color
{
    private const int MAX_LABEL_LENGTH = 16;

    public function __construct(
        private HexColor $colorCode,
        private string $label,
    ) {
        $this->validateLabel($label);
    }

    public function colorCode(): HexColor
    {
        return $this->colorCode;
    }

    public function label(): string
    {
        return $this->label;
    }

    /**
     * @return array{color_code: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'color_code' => (string) $this->colorCode,
            'label' => $this->label,
        ];
    }

    /**
     * @return array{colorCode: string, label: string}
     */
    public function toApiArray(): array
    {
        return [
            'colorCode' => (string) $this->colorCode,
            'label' => $this->label,
        ];
    }

    public function __toString(): string
    {
        return (string) $this->colorCode;
    }

    private function validateLabel(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidArgumentException('Color label cannot be empty.');
        }

        if (mb_strlen($label) > self::MAX_LABEL_LENGTH) {
            throw new InvalidArgumentException('Color label must be 16 characters or less.');
        }
    }
}
