<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\StrTestHelper;

class ListMyVideoLinkLikesInputTest extends TestCase
{
    public function testPreservesPrincipalAndIdentifiersAtMaximumBatchSize(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $ids = array_map(static fn (): VideoLinkIdentifier => new VideoLinkIdentifier(StrTestHelper::generateUuid()), range(1, 100));
        $input = new ListMyVideoLinkLikesInput($principal, $ids);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame($ids, $input->videoLinkIdentifiers());
        $single = new ListMyVideoLinkLikesInput($principal, [$ids[0]]);
        self::assertSame($ids[0], $single->videoLinkIdentifiers()[0]);
    }

    /** @return array<string, array{int}> */
    public static function invalidSizes(): array
    {
        return ['empty' => [0], 'over limit' => [101]];
    }

    #[DataProvider('invalidSizes')]
    public function testRejectsInvalidBatchSize(int $size): void
    {
        $ids = $size === 0 ? [] : array_fill(0, $size, new VideoLinkIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(InvalidArgumentException::class);
        new ListMyVideoLinkLikesInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), $ids);
    }
}
