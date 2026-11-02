<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;

class ResourceTypeTest extends TestCase
{
    public function testPersistedVocabulary(): void
    {
        self::assertSame(['analysis'], array_column(ResourceType::cases(), 'value'));
    }
}
