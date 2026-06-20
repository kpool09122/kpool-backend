<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Tests\TestCase;

class MetaDescriptionTest extends TestCase
{
    public function testCreatesMetaDescription(): void
    {
        $description = new MetaDescription(str_repeat('あ', MetaDescription::MAX_LENGTH));

        $this->assertSame(str_repeat('あ', MetaDescription::MAX_LENGTH), (string) $description);
    }

    public function testAllowsEmptyMetaDescription(): void
    {
        $description = new MetaDescription('');

        $this->assertSame('', (string) $description);
    }

    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MetaDescription(str_repeat('あ', MetaDescription::MAX_LENGTH + 1));
    }
}
