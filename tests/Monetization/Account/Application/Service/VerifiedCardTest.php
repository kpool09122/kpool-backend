<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\Service\VerifiedCard;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;

class VerifiedCardTest extends TestCase
{
    public function testPreservesVerifiedReferencesAndDisplayInformation(): void
    {
        $identifier = new ExternalPaymentMethodId('pm_test12345');
        $meta = new PaymentMethodMeta('visa', '4242', 12, 2030);
        $result = new VerifiedCard($identifier, $meta);
        self::assertSame($identifier, $result->externalPaymentMethodId);
        self::assertSame($meta, $result->meta);
    }
}
