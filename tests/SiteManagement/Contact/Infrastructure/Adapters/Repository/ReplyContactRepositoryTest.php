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
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
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
        $contact = new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );
        $contactRepository = $this->app->make(ContactRepositoryInterface::class);
        $contactRepository->save($contact);

        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $sentAt = new DateTimeImmutable('2026-01-01 12:34:56');
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contact->contactIdentifier(),
            $identityIdentifier,
            $contact->email(),
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
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
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
        $contact = new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );
        $contactRepository = $this->app->make(ContactRepositoryInterface::class);
        $contactRepository->save($contact);

        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contact->contactIdentifier(),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $contact->email(),
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
}
