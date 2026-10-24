<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Helper\CreateWikiSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class MultipleWikiSnapshotsMigrationTest extends TestCase
{
    public function testRollbackAndUpgradePreserveExistingHistoryAndIndexes(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        CreateWikiSnapshot::create($snapshotId, 'group');
        $before = DB::table('wiki_snapshots')->where('id', $snapshotId)->first();
        /** @var Migration $migration */
        $migration = require dirname(__DIR__, 5) . '/database/migrations/2026_10_25_000001_allow_multiple_wiki_snapshots_per_version.php';
        $rollback = [$migration, 'down'];
        self::assertIsCallable($rollback);
        $rollback();
        self::assertTrue(Schema::hasIndex('wiki_snapshots', 'wiki_snapshots_wiki_id_version_unique', 'unique'));
        self::assertEquals($before, DB::table('wiki_snapshots')->where('id', $snapshotId)->first());
        $upgrade = [$migration, 'up'];
        self::assertIsCallable($upgrade);
        $upgrade();
        self::assertTrue(Schema::hasIndex('wiki_snapshots', 'wiki_snapshots_version_order_index'));
        self::assertFalse(Schema::hasIndex('wiki_snapshots', 'wiki_snapshots_wiki_id_version_unique'));
        self::assertEquals($before, DB::table('wiki_snapshots')->where('id', $snapshotId)->first());
    }

    public function testRollbackRefusesDuplicatesWithoutDeletingHistory(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $firstId = StrTestHelper::generateUuid();
        $secondId = StrTestHelper::generateUuid();
        CreateWikiSnapshot::create($firstId, 'group', ['wiki_id' => $wikiId]);
        CreateWikiSnapshot::create($secondId, 'group', ['wiki_id' => $wikiId]);
        /** @var Migration $migration */
        $migration = require dirname(__DIR__, 5) . '/database/migrations/2026_10_25_000001_allow_multiple_wiki_snapshots_per_version.php';
        $rollback = [$migration, 'down'];
        self::assertIsCallable($rollback);

        try {
            $rollback();
            self::fail('Duplicate history must block rollback');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Export and preserve all history', $e->getMessage());
        }
        self::assertSame(2, DB::table('wiki_snapshots')->where('wiki_id', $wikiId)->count());
        self::assertTrue(Schema::hasIndex('wiki_snapshots', 'wiki_snapshots_version_order_index'));
        self::assertFalse(Schema::hasIndex('wiki_snapshots', 'wiki_snapshots_wiki_id_version_unique'));
    }
}
