<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;

class ResourceTypeTest extends TestCase
{
    public function testContract(): void
    {
        self::assertSame(['principal_group'], array_map(static fn (ResourceType $type): string => $type->value, ResourceType::cases()));
    }
}
