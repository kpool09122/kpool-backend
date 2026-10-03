<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContacts;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateUser;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListContactsTest extends TestCase
{
    public function testUseCaseIsBoundToInfrastructureQuery(): void
    {
        $this->assertSame(ListContacts::class, $this->app()->make(ListContactsInterface::class)::class);
    }

    #[Group('useDb')]
    public function testProcessReturnsOnlySpecifiedIdentityContactsForAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $older = StrTestHelper::generateUuid();
        $newer = StrTestHelper::generateUuid();
        $this->insertContact($older, (string) $target, 'older@example.com', '2026-08-15 10:00:00');
        $this->insertContact($newer, (string) $target, 'newer@example.com', '2026-08-16 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), 'other@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($requester, $target, null), $output);

        $this->assertSame([$newer, $older], array_column($output->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[], []], array_column($output->toArray()['contacts'], 'replyIdentifiers'));
        $this->assertSame([
            (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            (new DateTimeImmutable('2026-08-15 10:00:00'))->format(DateTimeInterface::ATOM),
        ], array_column($output->toArray()['contacts'], 'createdAt'));
    }

    #[Group('useDb')]
    public function testProcessRejectsNonAdmin(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::NONE]);

        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListContactsInterface::class)->process(
            new ListContactsInput($requester, new IdentityIdentifier(StrTestHelper::generateUuid()), null),
            new ListContactsOutput(),
        );
    }

    #[Group('useDb')]
    public function testProcessReturnsAllContactsIncludingAnonymousContactsForAdminWhenTargetIdentityIsNotSpecified(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $targetIdentityIdentifier = StrTestHelper::generateUuid();
        $identityContact = StrTestHelper::generateUuid();
        $anonymousContact = StrTestHelper::generateUuid();
        $this->insertContact($identityContact, $targetIdentityIdentifier, 'identity@example.com', '2026-08-15 10:00:00');
        $this->insertContact($anonymousContact, null, 'anonymous@example.com', '2026-08-16 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($requester, null, null), $output);

        $this->assertSame([$anonymousContact, $identityContact], array_column($output->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([null, $targetIdentityIdentifier], array_column($output->toArray()['contacts'], 'identityIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessReturnsRequestedContactPageAndPaginationMetadata(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $first = StrTestHelper::generateUuid();
        $second = StrTestHelper::generateUuid();
        $third = StrTestHelper::generateUuid();
        $this->insertContact($first, null, 'first@example.com', '2026-08-15 10:00:00');
        $this->insertContact($second, null, 'second@example.com', '2026-08-16 10:00:00');
        $this->insertContact($third, null, 'third@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(
            new ListContactsInput($requester, null, null, 2, 2),
            $output,
        );

        $this->assertSame([$first], array_column($output->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([
            'current_page' => 2,
            'last_page' => 2,
            'total' => 3,
            'per_page' => 2,
        ], array_intersect_key($output->toArray(), array_flip(['current_page', 'last_page', 'total', 'per_page'])));
    }

    #[Group('useDb')]
    public function testProcessFiltersContactsByWhetherTheyHaveASuccessfullySentReply(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        CreateUser::create(new UserIdentifier(StrTestHelper::generateUuid()), $requester, ['role' => Role::ADMIN]);
        $sentContact = StrTestHelper::generateUuid();
        $failedContact = StrTestHelper::generateUuid();
        $unrepliedContact = StrTestHelper::generateUuid();
        $sentReply = StrTestHelper::generateUuid();
        $laterReply = StrTestHelper::generateUuid();
        $this->insertContact($sentContact, null, 'sent@example.com', '2026-08-17 10:00:00');
        $this->insertContact($failedContact, null, 'failed@example.com', '2026-08-16 10:00:00');
        $this->insertContact($unrepliedContact, null, 'unreplied@example.com', '2026-08-15 10:00:00');
        $this->insertReply($sentReply, $sentContact, '2026-08-17 11:00:00', null);
        $this->insertReply($laterReply, $sentContact, '2026-08-17 12:00:00', null, '2026-08-17 12:00:00');
        $this->insertReply(StrTestHelper::generateUuid(), $sentContact, null, null);
        $this->insertReply(StrTestHelper::generateUuid(), $failedContact, '2026-08-16 11:00:00', '2026-08-16 11:01:00');

        $hasReplyOutput = new ListContactsOutput();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($requester, null, true), $hasReplyOutput);

        $this->assertCount(4, DB::getQueryLog());
        $this->assertSame([$sentContact], array_column($hasReplyOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[$sentReply, $laterReply]], array_column($hasReplyOutput->toArray()['contacts'], 'replyIdentifiers'));

        $hasNoReplyOutput = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($requester, null, false), $hasNoReplyOutput);

        $this->assertSame([$failedContact, $unrepliedContact], array_column($hasNoReplyOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[], []], array_column($hasNoReplyOutput->toArray()['contacts'], 'replyIdentifiers'));

        $allContactsOutput = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($requester, null, null), $allContactsOutput);

        $this->assertSame([$sentContact, $failedContact, $unrepliedContact], array_column($allContactsOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[$sentReply, $laterReply], [], []], array_column($allContactsOutput->toArray()['contacts'], 'replyIdentifiers'));
    }

    private function insertContact(string $id, ?string $identityIdentifier, string $email, string $createdAt): void
    {
        DB::table('contacts')->insert([
            'id' => $id,
            'identity_identifier' => $identityIdentifier,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt($email),
            'content' => 'お問い合わせ内容',
            'language' => 'ja',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function insertReply(string $id, string $contactIdentifier, ?string $sentAt, ?string $failedAt, string $createdAt = '2026-08-17 10:00:00'): void
    {
        DB::table('contact_replies')->insert([
            'id' => $id,
            'contact_id' => $contactIdentifier,
            'identity_identifier' => null,
            'to_email' => 'encrypted@example.com',
            'content' => '返信内容',
            'sent_at' => $sentAt,
            'failed_at' => $failedAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
