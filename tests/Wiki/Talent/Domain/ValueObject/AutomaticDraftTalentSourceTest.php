<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentSource;

class AutomaticDraftTalentSourceTest extends TestCase
{
    public function test__construct(): void
    {
        $source = 'news::talent-id';
        $automaticDraftTalentSource = new AutomaticDraftTalentSource($source);

        $this->assertSame($source, (string) $automaticDraftTalentSource);
    }

    public function testWithEmptyValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftTalentSource('');
    }

    public function testWithTooLongValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftTalentSource(str_repeat('a', AutomaticDraftTalentSource::MAX_LENGTH + 1));
    }
}
