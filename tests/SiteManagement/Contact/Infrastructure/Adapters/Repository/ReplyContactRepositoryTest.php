<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyType;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateReplyContact;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyContactRepositoryTest extends TestCase
{
    /**
     * 正常系：返信履歴を保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    #[DataProvider('replyTypeProvider')]
    public function testSave(ReplyType $replyType): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('contacts')->insert([
            'id' => (string)$contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => 'お名前',
            'email' => $encryptionService->encrypt((string)$toEmail),
            'content' => 'お問い合わせ内容',
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $sentAt = new DateTimeImmutable('2026-01-01 12:34:56');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $principalIdentifier,
            $toEmail,
            new ReplyContent($contentText),
            $sentAt,
            null,
            new DateTimeImmutable('2026-01-01 12:34:57'),
            $replyType,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('contact_replies')
            ->where('id', (string)$reply->replyIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame((string)$reply->contactIdentifier(), $record->contact_id);
        $this->assertSame((string)$principalIdentifier, $record->principal_identifier);
        $this->assertSame($replyType->value, $record->reply_type);
        $restored = $repository->findById($reply->replyIdentifier());
        $this->assertNotNull($restored);
        $this->assertSame($replyType, $restored->replyType());

        // 保存時は暗号化されていること（平文と一致しない）
        $this->assertNotSame((string)$reply->toEmail(), $record->to_email);
        $this->assertNotEmpty($record->to_email);
        $this->assertIsString($record->to_email);
        // 復号すると登録したメールアドレスと一致すること
        $this->assertSame((string)$reply->toEmail(), $encryptionService->decrypt($record->to_email));

        $this->assertSame((string)$reply->content(), $record->content);
        $this->assertIsString($record->sent_at);
        $this->assertSame($sentAt->format('Y-m-d H:i:s'), (new DateTimeImmutable($record->sent_at))->format('Y-m-d H:i:s'));
        $this->assertNull($record->failed_at);

        // 同じ履歴の送信状態を更新しても、保存・復元した種別を維持する。
        $failedAt = new DateTimeImmutable('2026-01-01 12:35:00');
        $updated = new ReplyCotact(
            $restored->replyIdentifier(),
            $restored->contactIdentifier(),
            $restored->principalIdentifier(),
            $restored->toEmail(),
            $restored->content(),
            null,
            $failedAt,
            $restored->createdAt(),
            $restored->replyType(),
        );
        $repository->save($updated);
        $updatedReply = $repository->findById($updated->replyIdentifier());
        $this->assertNotNull($updatedReply);
        $this->assertSame($replyType, $updatedReply->replyType());
        $this->assertSame((string) $principalIdentifier, (string) $updatedReply->principalIdentifier());
        $this->assertNull($updatedReply->sentAt());
        $this->assertEquals($failedAt, $updatedReply->failedAt());
        $this->assertEquals($restored->createdAt(), $updatedReply->createdAt());
        $this->assertSame(1, DB::table('contact_replies')->where('id', (string) $reply->replyIdentifier())->count());
    }

    /**
     * 正常系：failed_at が設定された返信履歴を保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithFailedAt(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('contacts')->insert([
            'id' => (string)$contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => 'お名前',
            'email' => $encryptionService->encrypt((string)$toEmail),
            'content' => 'お問い合わせ内容',
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $failedAt = new DateTimeImmutable('2026-01-01 12:34:58');
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $toEmail,
            new ReplyContent($contentText),
            null,
            $failedAt,
            new DateTimeImmutable('2026-01-01 12:34:57'),
            ReplyType::OPERATOR,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('contact_replies')
            ->where('id', (string)$reply->replyIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertNull($record->sent_at);
        $this->assertIsString($record->failed_at);
        $this->assertSame($failedAt->format('Y-m-d H:i:s'), (new DateTimeImmutable($record->failed_at))->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系：ID指定で返信履歴を取得できること（メールアドレスは復号されること）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    #[DataProvider('replyTypeProvider')]
    public function testFindById(ReplyType $replyType): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('contacts')->insert([
            'id' => (string)$contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => 'お名前',
            'email' => $encryptionService->encrypt((string)$toEmail),
            'content' => 'お問い合わせ内容',
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $reply = CreateReplyContact::create(
            $contactIdentifier,
            $toEmail,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable('2026-01-02 12:34:56'),
            null,
            new DateTimeImmutable('2026-01-02 00:00:00'),
            '返信内容です',
            $encryptionService,
            $replyType,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $savedReply = $repository->findById($reply->replyIdentifier());

        $this->assertNotNull($savedReply);
        $this->assertSame((string)$reply->replyIdentifier(), (string)$savedReply->replyIdentifier());
        $this->assertSame($replyType, $savedReply->replyType());
        $this->assertSame((string)$contactIdentifier, (string)$savedReply->contactIdentifier());
        $this->assertNotNull($savedReply->principalIdentifier());
        $this->assertSame((string)$reply->principalIdentifier(), (string)$savedReply->principalIdentifier());
        $this->assertSame((string)$toEmail, (string)$savedReply->toEmail());
        $this->assertSame((string)$reply->content(), (string)$savedReply->content());
        $this->assertNotNull($savedReply->sentAt());
        $this->assertSame($reply->sentAt()?->format('Y-m-d H:i:s'), $savedReply->sentAt()->format('Y-m-d H:i:s'));
        $this->assertNull($savedReply->failedAt());
        $this->assertSame($reply->createdAt()->format('Y-m-d H:i:s'), $savedReply->createdAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系：principal_identifier / sent_at が null かつ failed_at がある場合も取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithNullIdentityAndNullSentAt(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('contacts')->insert([
            'id' => (string)$contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => 'お名前',
            'email' => $encryptionService->encrypt((string)$toEmail),
            'content' => 'お問い合わせ内容',
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $reply = CreateReplyContact::create(
            $contactIdentifier,
            $toEmail,
            null,
            null,
            new DateTimeImmutable('2026-01-03 12:34:56'),
            new DateTimeImmutable('2026-01-03 00:00:00'),
            '返信内容です（nullケース）',
            $encryptionService,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $savedReply = $repository->findById($reply->replyIdentifier());

        $this->assertNotNull($savedReply);
        $this->assertNull($savedReply->principalIdentifier());
        $this->assertSame(ReplyType::OPERATOR, $savedReply->replyType());
        $this->assertNull($savedReply->sentAt());
        $this->assertNotNull($savedReply->failedAt());
        $this->assertNotNull($reply->failedAt());
        $this->assertNotNull($savedReply->failedAt());
        $this->assertSame($reply->failedAt()->format('Y-m-d H:i:s'), $savedReply->failedAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$toEmail, (string)$savedReply->toEmail());
        $this->assertSame($reply->createdAt()->format('Y-m-d H:i:s'), $savedReply->createdAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系：存在しないIDの場合は null を返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $reply = $repository->findById(new ContactReplyIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($reply);
    }

    /** @return array<string, array{ReplyType}> */
    public static function replyTypeProvider(): array
    {
        return [
            'operator' => [ReplyType::OPERATOR],
            'submitter' => [ReplyType::SUBMITTER],
        ];
    }

    #[Group('useDb')]
    public function testReplyTypeMigrationPreservesExistingHistoryAndRollsBack(): void
    {
        $migration = require __DIR__ . '/../../../../../../database/migrations/2026_10_20_000001_add_reply_type_to_contact_replies_table.php';
        $this->assertInstanceOf(Migration::class, $migration);
        $up = [$migration, 'up'];
        $down = [$migration, 'down'];
        $this->assertIsCallable($up);
        $this->assertIsCallable($down);
        $down();
        $this->assertFalse(Schema::hasColumn('contact_replies', 'reply_type'));

        $contactIdentifier = StrTestHelper::generateUuid();
        DB::table('contacts')->insert([
            'id' => $contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => 'legacy-encrypted-email',
            'content' => '既存のお問い合わせ',
        ]);
        foreach (['pending', 'sent', 'failed'] as $status) {
            DB::table('contact_replies')->insert([
                'id' => StrTestHelper::generateUuid(),
                'contact_id' => $contactIdentifier,
                'principal_identifier' => $status === 'pending' ? null : StrTestHelper::generateUuid(),
                'to_email' => 'legacy-encrypted-email',
                'content' => $status,
                'sent_at' => $status === 'sent' ? '2026-10-19 10:00:00' : null,
                'failed_at' => $status === 'failed' ? '2026-10-19 10:01:00' : null,
                'created_at' => '2026-10-19 09:00:00',
                'updated_at' => '2026-10-19 10:01:00',
            ]);
        }
        $before = DB::table('contact_replies')->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all();

        $up();

        $after = DB::table('contact_replies')->orderBy('id')->get()->map(static function (object $row): array {
            $record = (array) $row;
            self::assertSame('operator', $record['reply_type']);
            unset($record['reply_type']);

            return $record;
        })->all();
        $this->assertSame($before, $after);
        $columns = array_column(Schema::getColumns('contact_replies'), null, 'name');
        $this->assertArrayHasKey('reply_type', $columns);
        $this->assertIsArray($columns['reply_type']);
        $this->assertSame('varchar', $columns['reply_type']['type_name']);
        $this->assertFalse($columns['reply_type']['nullable']);
        $this->assertIsString($columns['reply_type']['default']);
        $this->assertStringContainsString('operator', $columns['reply_type']['default']);

        $down();

        $this->assertFalse(Schema::hasColumn('contact_replies', 'reply_type'));
        $this->assertSame($before, DB::table('contact_replies')->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all());
        $up();
        $this->assertSame(3, DB::table('contact_replies')->where('reply_type', 'operator')->count());
    }

    #[Group('useDb')]
    #[DataProvider('invalidReplyTypeProvider')]
    public function testReplyTypeRejectsInvalidValues(?string $replyType, string $sqlState): void
    {
        $contactIdentifier = StrTestHelper::generateUuid();
        DB::table('contacts')->insert([
            'id' => $contactIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => 'encrypted-email',
            'content' => 'お問い合わせ',
        ]);

        try {
            DB::transaction(static function () use ($contactIdentifier, $replyType): void {
                DB::table('contact_replies')->insert([
                    'id' => StrTestHelper::generateUuid(),
                    'contact_id' => $contactIdentifier,
                    'to_email' => 'encrypted-email',
                    'content' => '返信',
                    'reply_type' => $replyType,
                ]);
            });
            $this->fail('Invalid reply_type was accepted.');
        } catch (QueryException $exception) {
            $this->assertNotNull($exception->errorInfo);
            $this->assertArrayHasKey(0, $exception->errorInfo);
            $this->assertSame($sqlState, $exception->errorInfo[0]);
        }
    }

    /** @return array<string, array{?string, string}> */
    public static function invalidReplyTypeProvider(): array
    {
        return [
            'null' => [null, '23502'],
            'unknown' => ['unknown', '23514'],
        ];
    }
}
