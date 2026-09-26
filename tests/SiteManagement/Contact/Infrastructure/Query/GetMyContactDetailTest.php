<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\CreateReplyContact;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetMyContactDetailTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsContactAndOnlySuccessfullySentRepliesForOwner(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, (string) $identityIdentifier, 'お問い合わせ内容');
        $encryptionService = $this->app->make(EncryptionServiceInterface::class);
        $sentReply = CreateReplyContact::create(
            new ContactIdentifier($contactIdentifier),
            new Email('contact@example.com'),
            $identityIdentifier,
            new DateTimeImmutable('2026-08-17 10:00:00'),
            null,
            new DateTimeImmutable('2026-08-17 09:00:00'),
            '送信成功した返信',
            $encryptionService,
        );
        CreateReplyContact::create(
            new ContactIdentifier($contactIdentifier),
            new Email('contact@example.com'),
            $identityIdentifier,
            null,
            new DateTimeImmutable('2026-08-17 10:01:00'),
            new DateTimeImmutable('2026-08-17 10:01:00'),
            '送信失敗した返信',
            $encryptionService,
        );

        $output = new GetMyContactDetailOutput();
        $this->app->make(GetMyContactDetailInterface::class)->process(
            new GetMyContactDetailInput($identityIdentifier, new ContactIdentifier($contactIdentifier)),
            $output,
        );

        $this->assertSame([
            'contactIdentifier' => $contactIdentifier,
            'identityIdentifier' => (string) $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'createdAt' => (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            'content' => 'お問い合わせ内容',
            'replies' => [[
                'replyIdentifier' => (string) $sentReply->replyIdentifier(),
                'content' => '送信成功した返信',
                'sentAt' => (new DateTimeImmutable('2026-08-17 10:00:00'))->format(DateTimeInterface::ATOM),
            ]],
        ], $output->toArray());
    }

    #[Group('useDb')]
    public function testProcessRejectsContactOwnedByAnotherIdentity(): void
    {
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, StrTestHelper::generateUuid(), '他人のお問い合わせ内容');

        $this->expectException(ContactNotFoundException::class);
        $this->app->make(GetMyContactDetailInterface::class)->process(
            new GetMyContactDetailInput(new IdentityIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier($contactIdentifier)),
            new GetMyContactDetailOutput(),
        );
    }

    private function insertContact(string $id, string $identityIdentifier, string $content): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'identity_identifier' => $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => $this->app->make(EncryptionServiceInterface::class)->encrypt('contact@example.com'),
            'content' => $content,
            'language' => 'ja',
            'created_at' => '2026-08-16 10:00:00',
            'updated_at' => '2026-08-16 10:00:00',
        ]);
    }
}
