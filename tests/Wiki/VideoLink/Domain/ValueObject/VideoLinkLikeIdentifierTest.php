<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkLikeIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkLikeIdentifierTest extends TestCase
{
    public function testPreservesUuid(): void
    {
        $uuid = StrTestHelper::generateUuid();
        self::assertSame($uuid, (string) new VideoLinkLikeIdentifier($uuid));
    }

    /** @return array<string, array{string}> */
    public static function invalidIdentifiers(): array
    {
        return ['empty' => [''], 'malformed' => ['not-a-uuid']];
    }

    #[DataProvider('invalidIdentifiers')]
    public function testRejectsInvalidUuid(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoLinkLikeIdentifier($value);
    }
}
