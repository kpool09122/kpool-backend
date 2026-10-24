<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListMyVideoLinkLikesTest extends TestCase
{
    public function testReturnsOnlyRequestedIdsLikedByAuthenticatedPrincipalInStableOrder(): void
    {
        $principals = [];
        foreach ([0, 1] as $i) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            $principals[$i] = new PrincipalIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
            CreatePrincipal::create($principals[$i], $identity);
        }
        $ids = array_map(static fn (): VideoLinkIdentifier => new VideoLinkIdentifier(StrTestHelper::generateUuid()), range(1, 5));
        foreach ($ids as $id) {
            CreateVideoLink::create((string) $id);
        }
        foreach ([[$ids[0], $principals[0]], [$ids[1], $principals[0]], [$ids[2], $principals[0]], [$ids[3], $principals[1]]] as [$video, $principal]) {
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        }
        $output = new ListMyVideoLinkLikesOutput();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principals[0], [$ids[1], $ids[0], $ids[1], $ids[3], $ids[4], new VideoLinkIdentifier(StrTestHelper::generateUuid())]), $output);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $expected = [(string) $ids[0], (string) $ids[1]];
        sort($expected);
        self::assertSame(['likedVideoLinkIdentifiers' => $expected], $output->toArray());
        self::assertCount(1, $queries);
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principals[1], [$ids[0], $ids[4]]), $output);
        self::assertSame(['likedVideoLinkIdentifiers' => []], $output->toArray());
    }
}
