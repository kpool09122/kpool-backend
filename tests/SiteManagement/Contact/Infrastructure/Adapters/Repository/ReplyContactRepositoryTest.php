<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
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
    public function testSave(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $identityIdentifier,
            $toEmail,
            new ReplyContent($contentText),
            ReplyStatus::SENT,
            $sentAt,
            new DateTimeImmutable('2026-01-01 12:34:57'),
        );

        $repository = $this->app->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('contact_replies')
            ->where('id', (string)$reply->replyIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame((string)$reply->contactIdentifier(), (string)$record->contact_id);
        $this->assertSame((string)$identityIdentifier, (string)$record->identity_identifier);

        // 保存時は暗号化されていること（平文と一致しない）
        $this->assertNotSame((string)$reply->toEmail(), $record->to_email);
        $this->assertNotEmpty($record->to_email);
        // 復号すると登録したメールアドレスと一致すること
        $this->assertSame((string)$reply->toEmail(), $encryptionService->decrypt($record->to_email));

        $this->assertSame((string)$reply->content(), (string)$record->content);
        $this->assertSame($reply->status()->value, (int)$record->status);

        $this->assertNotNull($record->sent_at);
        $this->assertSame($sentAt->format('Y-m-d H:i:s'), (new DateTimeImmutable((string)$record->sent_at))->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系：sent_at が null の場合も保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullSentAt(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
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
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $toEmail,
            new ReplyContent($contentText),
            ReplyStatus::FAILED,
            null,
            new DateTimeImmutable('2026-01-01 12:34:57'),
        );

        $repository = $this->app->make(ReplyContactRepositoryInterface::class);
        $repository->save($reply);

        $record = DB::table('contact_replies')
            ->where('id', (string)$reply->replyIdentifier())
            ->first();

        $this->assertNotNull($record);
        $this->assertNull($record->sent_at);
        $this->assertSame(ReplyStatus::FAILED->value, (int)$record->status);
    }

    /**
     * 正常系：ID指定で返信履歴を取得できること（メールアドレスは復号されること）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
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
            ReplyStatus::SENT,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable('2026-01-02 12:34:56'),
            new DateTimeImmutable('2026-01-02 00:00:00'),
            '返信内容です',
            $encryptionService,
        );

        $repository = $this->app->make(ReplyContactRepositoryInterface::class);
        $savedReply = $repository->findById($reply->replyIdentifier());

        $this->assertNotNull($savedReply);
        $this->assertSame((string)$reply->replyIdentifier(), (string)$savedReply->replyIdentifier());
        $this->assertSame((string)$contactIdentifier, (string)$savedReply->contactIdentifier());
        $this->assertNotNull($savedReply->identityIdentifier());
        $this->assertSame((string)$reply->identityIdentifier(), (string)$savedReply->identityIdentifier());
        $this->assertSame((string)$toEmail, (string)$savedReply->toEmail());
        $this->assertSame((string)$reply->content(), (string)$savedReply->content());
        $this->assertSame($reply->status()->value, $savedReply->status()->value);
        $this->assertNotNull($savedReply->sentAt());
        $this->assertSame($reply->sentAt()?->format('Y-m-d H:i:s'), $savedReply->sentAt()->format('Y-m-d H:i:s'));
        $this->assertSame($reply->createdAt()->format('Y-m-d H:i:s'), $savedReply->createdAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系：identity_identifier / sent_at が null の場合も取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithNullIdentityAndNullSentAt(): void
    {
        // ReplyContactRepository のテストでは ContactRepository の実装に依存したくないため、
        // 外部キー制約を満たす最小限の contacts レコードはDBへ直接作成する。
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
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
            ReplyStatus::FAILED,
            null,
            null,
            new DateTimeImmutable('2026-01-03 00:00:00'),
            '返信内容です（nullケース）',
            $encryptionService,
        );

        $repository = $this->app->make(ReplyContactRepositoryInterface::class);
        $savedReply = $repository->findById($reply->replyIdentifier());

        $this->assertNotNull($savedReply);
        $this->assertNull($savedReply->identityIdentifier());
        $this->assertNull($savedReply->sentAt());
        $this->assertSame($reply->status()->value, $savedReply->status()->value);
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
        $repository = $this->app->make(ReplyContactRepositoryInterface::class);
        $reply = $repository->findById(new ContactReplyIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($reply);
    }
}
