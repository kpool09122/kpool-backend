<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes;

use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;

class ListMyVideoLinkLikesOutputTest extends TestCase
{
    public function testSerializesEmptyListAndReplacesPreviousResults(): void
    {
        $output = new ListMyVideoLinkLikesOutput();
        self::assertSame(['likedVideoLinkIdentifiers' => []], $output->toArray());
        $output->setLikedVideoLinkIdentifiers(['id1', 'id2']);
        self::assertSame(['likedVideoLinkIdentifiers' => ['id1', 'id2']], $output->toArray());
        $output->setLikedVideoLinkIdentifiers([]);
        self::assertSame(['likedVideoLinkIdentifiers' => []], $output->toArray());
    }
}
