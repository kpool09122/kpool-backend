<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use Source\Wiki\Shared\Domain\ValueObject\Version;

final readonly class TranslationSource
{
    public function __construct(
        private WikiIdentifier $wikiIdentifier,
        private Version $version,
    ) {
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function equals(self $other): bool
    {
        return (string) $this->wikiIdentifier === (string) $other->wikiIdentifier
            && $this->version->value() === $other->version->value();
    }
}
