<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\Effect;

class EffectTest extends TestCase
{
    public function testSerializedValuesAreUniqueAndRoundTrip(): void
    {
        $values = [];
        foreach (Effect::cases() as $case) {
            self::assertSame($case, Effect::from($case->value));
            $values[] = $case->value;
        } self::assertSame($values, array_values(array_unique($values)));
    }
}
