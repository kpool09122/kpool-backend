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
