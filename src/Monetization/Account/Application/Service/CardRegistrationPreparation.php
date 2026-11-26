<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Service;

use SensitiveParameter;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;

readonly class CardRegistrationPreparation
{
    public function __construct(public SetupIntentIdentifier $identifier, #[SensitiveParameter] public string $clientSecret)
    {
    }
}
