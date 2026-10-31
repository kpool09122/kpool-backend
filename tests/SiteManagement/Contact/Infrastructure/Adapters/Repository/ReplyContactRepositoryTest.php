<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Reply;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
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
    #[DataProvider('replyNumberProvider')]
    public function testSave(int $replyNumber): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の site_management_contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('site_management_contacts')->insert([
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
        $reply = new Reply(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $principalIdentifier,
            $toEmail,
            new ReplyContent($contentText),
            $sentAt,
            null,
            new DateTimeImmutable('2026-01-01 12:34:57'),
            $replyNumber,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('site_management_contact_replies')
            ->where('id', (string)$reply->replyIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame((string)$reply->contactIdentifier(), $record->contact_id);
        $this->assertSame((string)$principalIdentifier, $record->principal_identifier);
        $this->assertSame($replyNumber, $record->reply_number);
        $restored = $repository->findById($reply->replyIdentifier());
        $this->assertNotNull($restored);
        $this->assertSame($replyNumber, $restored->replyNumber());

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

        // 同じ履歴の送信状態を更新しても、保存・復元した連番を維持する。
        $failedAt = new DateTimeImmutable('2026-01-01 12:35:00');
        $updated = new Reply(
            $restored->replyIdentifier(),
            $restored->contactIdentifier(),
            $restored->principalIdentifier(),
            $restored->toEmail(),
            $restored->content(),
            null,
            $failedAt,
            $restored->createdAt(),
            $restored->replyNumber(),
        );
        $repository->save($updated);
        $updatedReply = $repository->findById($updated->replyIdentifier());
        $this->assertNotNull($updatedReply);
        $this->assertSame($replyNumber, $updatedReply->replyNumber());
        $this->assertSame((string) $principalIdentifier, (string) $updatedReply->principalIdentifier());
        $this->assertNull($updatedReply->sentAt());
        $this->assertEquals($failedAt, $updatedReply->failedAt());
        $this->assertEquals($restored->createdAt(), $updatedReply->createdAt());
        $this->assertSame(1, DB::table('site_management_contact_replies')->where('id', (string) $reply->replyIdentifier())->count());
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
        // 外部キー制約を満たす最小限の site_management_contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('site_management_contacts')->insert([
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
        $reply = new Reply(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $toEmail,
            new ReplyContent($contentText),
            null,
            $failedAt,
            new DateTimeImmutable('2026-01-01 12:34:57'),
            1,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('site_management_contact_replies')
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
    #[DataProvider('replyNumberProvider')]
    public function testFindById(int $replyNumber): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の site_management_contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('site_management_contacts')->insert([
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
            $replyNumber,
        );

        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $savedReply = $repository->findById($reply->replyIdentifier());

        $this->assertNotNull($savedReply);
        $this->assertSame((string)$reply->replyIdentifier(), (string)$savedReply->replyIdentifier());
        $this->assertSame($replyNumber, $savedReply->replyNumber());
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
        // 外部キー制約を満たす最小限の site_management_contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        DB::table('site_management_contacts')->insert([
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
        $this->assertSame(1, $savedReply->replyNumber());
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

    #[Group('useDb')]
    public function testLatestParticipantRepliesReturnNullWithoutHistory(): void
    {
        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $submitter = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->assertNull($repository->findLatestFromSubmitter($contactIdentifier, $submitter));
        $this->assertNull($repository->findLatestFromOperator($contactIdentifier, $submitter));
    }

    #[Group('useDb')]
    public function testLatestParticipantRepliesAreScopedAndOrderedBySequenceIncludingUnsentEntries(): void
    {
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $repository = $this->app()->make(ReplyContactRepositoryInterface::class);
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $otherContactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $submitter = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $operator = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $email = new Email('member@example.com');
        $now = new DateTimeImmutable('2026-01-01');
        foreach ([$contactIdentifier, $otherContactIdentifier] as $identifier) {
            DB::table('site_management_contacts')->insert([
                'id' => (string) $identifier,
                'category' => Category::SUGGESTIONS->value,
                'name' => 'Member',
                'email' => $encryptionService->encrypt((string) $email),
                'content' => 'Original inquiry',
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        }
        CreateReplyContact::create($contactIdentifier, $email, $submitter, $now, null, $now->modify('+1 day'), 'Older submitter reply', $encryptionService, 1);
        CreateReplyContact::create($contactIdentifier, $email, $operator, $now, null, $now->modify('+1 day'), 'Older operator reply', $encryptionService, 2);
        $latestSubmitter = CreateReplyContact::create($contactIdentifier, $email, $submitter, null, null, $now, 'Unsent submitter reply', $encryptionService, 3);
        $latestOperator = CreateReplyContact::create($contactIdentifier, $email, null, null, $now, $now, 'Historical operator reply with failed delivery', $encryptionService, 4);
        CreateReplyContact::create($otherContactIdentifier, $email, $operator, $now, null, $now, 'Other conversation', $encryptionService, 99);
        CreateReplyContact::create($otherContactIdentifier, $email, $submitter, $now, null, $now, 'Other submitter conversation', $encryptionService, 100);

        $this->assertSame((string) $latestSubmitter->replyIdentifier(), (string) $repository->findLatestFromSubmitter($contactIdentifier, $submitter)?->replyIdentifier());
        $this->assertSame((string) $latestOperator->replyIdentifier(), (string) $repository->findLatestFromOperator($contactIdentifier, $submitter)?->replyIdentifier());
    }

    /** @return array<string, array{int}> */
    public static function replyNumberProvider(): array
    {
        return [
            'first' => [1],
            'later' => [7],
        ];
    }
}
