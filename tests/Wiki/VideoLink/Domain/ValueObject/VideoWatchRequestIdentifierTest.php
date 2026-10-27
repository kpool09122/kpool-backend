<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoWatchRequestIdentifier;
use Tests\Helper\StrTestHelper;

class VideoWatchRequestIdentifierTest extends TestCase
{
    public function testPreservesUuid(): void
    {
        $id = StrTestHelper::generateUuid();
        self::assertSame($id, (string) new VideoWatchRequestIdentifier($id));
    }

    public function testRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoWatchRequestIdentifier('invalid');
    }
}
