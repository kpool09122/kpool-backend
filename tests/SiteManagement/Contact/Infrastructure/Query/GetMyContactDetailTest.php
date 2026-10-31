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
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetMyContactDetailTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsContactAndAllSavedRepliesForOwner(): void
    {
        $principalIdentifier = SiteManagementAuthorization::bind(new IdentityIdentifier(StrTestHelper::generateUuid()));
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, (string) $principalIdentifier, 'お問い合わせ内容');
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $sentReply = CreateReplyContact::create(
            new ContactIdentifier($contactIdentifier),
            new Email('contact@example.com'),
            $principalIdentifier,
            new DateTimeImmutable('2026-08-17 10:00:00'),
            null,
            new DateTimeImmutable('2026-08-17 09:00:00'),
            '送信成功した返信',
            $encryptionService,
        );
        $failedReply = CreateReplyContact::create(
            new ContactIdentifier($contactIdentifier),
            new Email('contact@example.com'),
            $principalIdentifier,
            null,
            new DateTimeImmutable('2026-08-17 10:01:00'),
            new DateTimeImmutable('2026-08-17 10:01:00'),
            '送信失敗した返信',
            $encryptionService,
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        $output = new GetMyContactDetailOutput();
        $this->app()->make(GetMyContactDetailInterface::class)->process(
            new GetMyContactDetailInput($principalIdentifier, new ContactIdentifier($contactIdentifier)),
            $output,
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'from "identities"') || str_contains($query['query'], 'from "site_management_principals"')));
        $replyQueries = array_values(array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'from "site_management_contact_replies"')));
        $this->assertCount(1, $replyQueries);
        $this->assertStringContainsString('principal_identifier', $replyQueries[0]['query']);

        $this->assertSame([
            'contactIdentifier' => $contactIdentifier,
            'principalIdentifier' => (string) $principalIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'createdAt' => (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            'content' => 'お問い合わせ内容',
            'replies' => array_map(static fn ($reply): array => [
                'replyIdentifier' => (string) $reply->replyIdentifier(),
                'content' => (string) $reply->content(),
                'sentAt' => $reply->sentAt()?->format(DateTimeInterface::ATOM),
                'replyNumber' => $reply->replyNumber(),
                'createdAt' => $reply->createdAt()->format(DateTimeInterface::ATOM),
                'principalIdentifier' => (string) $principalIdentifier,
                'name' => null,
            ], [$sentReply, $failedReply]),
        ], $output->toArray());
    }

    #[Group('useDb')]
    public function testProcessRejectsContactOwnedByAnotherPrincipal(): void
    {
        $contactIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherPrincipalIdentifier = SiteManagementAuthorization::bind($identityIdentifier);
        $this->insertContact($contactIdentifier, (string) $otherPrincipalIdentifier, '別アカウントのお問い合わせ内容');

        $principalIdentifier = SiteManagementAuthorization::bind($identityIdentifier);
        $this->expectException(ContactNotFoundException::class);
        $this->app()->make(GetMyContactDetailInterface::class)->process(
            new GetMyContactDetailInput($principalIdentifier, new ContactIdentifier($contactIdentifier)),
            new GetMyContactDetailOutput(),
        );
    }

    private function insertContact(string $id, string $principalIdentifier, string $content): void
    {
        DB::table('site_management_contacts')->insert([
            'id' => $id,
            'principal_identifier' => $principalIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt('contact@example.com'),
            'content' => $content,
            'language' => 'ja',
            'created_at' => '2026-08-16 10:00:00',
            'updated_at' => '2026-08-16 10:00:00',
        ]);
    }
}
