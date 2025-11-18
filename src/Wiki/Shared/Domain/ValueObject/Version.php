<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Version
{
    public function __construct(
        private int $version
    ) {
        $this->validate($version);
    }

    public function value(): int
    {
        return $this->version;
    }

    public function validate(int $version): void
    {
        if ($version < 1) {
            throw new InvalidArgumentException('Version must be a positive integer');
        }
    }

    public static function nextVersion(Version $version): self
    {
        return new self($version->value() + 1);
    }
}
