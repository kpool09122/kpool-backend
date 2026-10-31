<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ContactReplyNumberMigrationTest extends TestCase
{
    public function testChangesSchemaAndRollsBackOnEmptyTable(): void
    {
        $migration = require __DIR__ . '/../../../../../../database/migrations/2026_10_31_000001_replace_contact_reply_type_with_reply_number.php';
        $this->assertInstanceOf(Migration::class, $migration);
        $down = [$migration, 'down'];
        $up = [$migration, 'up'];
        $this->assertIsCallable($down);
        $this->assertIsCallable($up);
        $this->assertTrue(Schema::hasColumn('contact_replies', 'reply_number'));
        $this->assertFalse(Schema::hasColumn('contact_replies', 'reply_type'));

        $down();

        $this->assertFalse(Schema::hasColumn('contact_replies', 'reply_number'));
        $this->assertTrue(Schema::hasColumn('contact_replies', 'reply_type'));

        $up();

        $this->assertTrue(Schema::hasColumn('contact_replies', 'reply_number'));
        $this->assertFalse(Schema::hasColumn('contact_replies', 'reply_type'));
    }

    /** @return array<string, array{?int, string}> */
    public static function invalidNumbers(): array
    {
        return ['null' => [null, '23502'], 'zero' => [0, '23514'], 'negative' => [-1, '23514'], 'duplicate' => [1, '23505']];
    }

    #[DataProvider('invalidNumbers')]
    public function testDatabaseRejectsInvalidOrDuplicateNumbers(?int $number, string $sqlState): void
    {
        $contact = $this->contact();
        $record = ['id' => StrTestHelper::generateUuid(), 'contact_id' => $contact, 'reply_number' => 1, 'content' => '本文', 'to_email' => 'encrypted'];
        DB::table('contact_replies')->insert($record);

        try {
            DB::transaction(static function () use ($record, $number): void {
                DB::table('contact_replies')->insert(array_replace($record, ['id' => StrTestHelper::generateUuid(), 'reply_number' => $number]));
            });
            $this->fail('Invalid reply number was accepted.');
        } catch (QueryException $exception) {
            $this->assertSame($sqlState, $exception->errorInfo[0] ?? null);
        }
    }

    private function contact(): string
    {
        $id = StrTestHelper::generateUuid();
        DB::table('contacts')->insert(['id' => $id, 'category' => 1, 'name' => '本人', 'email' => 'encrypted', 'content' => '問い合わせ']);

        return $id;
    }
}
