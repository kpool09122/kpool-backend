<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

readonly class WebAuthnOptions
{
    /** @param array<string, mixed> $options */
    public function __construct(
        private string $optionsJson,
        private array $options,
    ) {
    }

    public function optionsJson(): string
    {
        return $this->optionsJson;
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        return $this->options;
    }
}
