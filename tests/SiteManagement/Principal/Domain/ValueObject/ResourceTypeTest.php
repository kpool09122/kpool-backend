<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;

class ResourceTypeTest extends TestCase
{
    public function testSerializedValuesAreUniqueAndRoundTrip(): void
    {
        $values = [];
        foreach (ResourceType::cases() as $case) {
            self::assertSame($case, ResourceType::from($case->value));
            $values[] = $case->value;
        } self::assertSame($values, array_values(array_unique($values)));
    }
}
