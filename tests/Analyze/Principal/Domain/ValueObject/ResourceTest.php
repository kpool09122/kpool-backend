<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\ValueObject\Resource;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;

class ResourceTest extends TestCase
{
    public function testGlobalAnalysisResourceIsNotLimitedToOwnedWikis(): void
    {
        self::assertSame(ResourceType::ANALYSIS, (new Resource())->type());
    }
}
