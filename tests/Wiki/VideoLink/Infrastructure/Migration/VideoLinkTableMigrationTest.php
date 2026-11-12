<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoLinkTableMigrationTest extends TestCase
{
    public function testDroppingVideoUsagePreservesVideosAndTheirCreators(): void
    {
        /** @var Migration $migration */
        $migration = require dirname(__DIR__, 5) . '/database/migrations/2026_11_12_000000_drop_video_usage_from_video_links_table.php';

        $legacyUsages = [
            'music_video' => false,
            'short' => false,
            'youtube_auto_view_count' => true,
            'youtube_auto_like_count' => true,
            'youtube_auto_recent_popular' => true,
        ];
        $identifiers = [];
        foreach ($legacyUsages as $usage => $isAutoCollected) {
            $identifier = StrTestHelper::generateUuid();
            CreateVideoLink::create($identifier, ['creator_principal_id' => $isAutoCollected ? null : StrTestHelper::generateUuid(), 'duration_seconds' => 253]);
            $identifiers[$usage] = $identifier;
        }
        $before = DB::table('wiki_video_links')->whereIn('id', $identifiers)->orderBy('id')->get()->toArray();

        $rollback = [$migration, 'down'];
        self::assertIsCallable($rollback);
        $rollback();
        foreach ($identifiers as $usage => $identifier) {
            DB::table('wiki_video_links')->where('id', $identifier)->update(['video_usage' => $usage]);
        }
        $migrate = [$migration, 'up'];
        self::assertIsCallable($migrate);
        $migrate();

        self::assertFalse(Schema::hasColumn('wiki_video_links', 'video_usage'));
        self::assertEquals($before, DB::table('wiki_video_links')->whereIn('id', $identifiers)->orderBy('id')->get()->toArray());
    }

    public function testRenameAndRollbackPreserveExistingVideos(): void
    {
        $identifier = StrTestHelper::generateUuid();
        CreateVideoLink::create($identifier, ['duration_seconds' => 253]);
        $before = DB::table('wiki_video_links')->where('id', $identifier)->first();
        self::assertNotNull($before);
        $expected = (array) $before;
        unset($expected['creator_principal_id']);
        $expected['display_order'] = 0;

        /** @var Migration $migration */
        $migration = require dirname(__DIR__, 5) . '/database/migrations/2026_10_23_000001_add_creator_to_video_links_table.php';
        $rollback = [$migration, 'down'];
        self::assertIsCallable($rollback);
        $rollback();

        self::assertFalse(Schema::hasTable('wiki_video_links'));
        self::assertTrue(Schema::hasTable('video_links'));
        self::assertFalse(Schema::hasColumn('video_links', 'creator_principal_id'));
        self::assertTrue(Schema::hasColumn('video_links', 'display_order'));
        self::assertEquals($expected, (array) DB::table('video_links')->where('id', $identifier)->first());

        $migrate = [$migration, 'up'];
        self::assertIsCallable($migrate);
        $migrate();

        self::assertFalse(Schema::hasTable('video_links'));
        self::assertTrue(Schema::hasTable('wiki_video_links'));
        self::assertTrue(Schema::hasColumn('wiki_video_links', 'creator_principal_id'));
        self::assertFalse(Schema::hasColumn('wiki_video_links', 'display_order'));
        self::assertEquals($before, DB::table('wiki_video_links')->where('id', $identifier)->first());
    }
}
