<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;

class CardRegistrationPreparationTest extends TestCase
{
    public function testPreservesProviderResult(): void
    {
        $identifier = new SetupIntentIdentifier('seti_test123');
        $result = new CardRegistrationPreparation($identifier, 'secret_test');
        self::assertSame($identifier, $result->identifier);
        self::assertSame('secret_test', $result->clientSecret);
    }
}
