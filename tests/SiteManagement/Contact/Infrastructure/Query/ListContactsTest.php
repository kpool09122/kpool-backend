<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Infrastructure\Query\ListContacts;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAuthorization;
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
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $older = StrTestHelper::generateUuid();
        $newer = StrTestHelper::generateUuid();
        $this->insertContact($older, (string) $target, 'older@example.com', '2026-08-15 10:00:00');
        $this->insertContact($newer, (string) $target, 'newer@example.com', '2026-08-16 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), 'other@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principalIdentifier, $target, null), $output);

        $this->assertSame([$newer, $older], array_column($output->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[], []], array_column($output->toArray()['contacts'], 'replyIdentifiers'));
        $this->assertSame([
            (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            (new DateTimeImmutable('2026-08-15 10:00:00'))->format(DateTimeInterface::ATOM),
        ], array_column($output->toArray()['contacts'], 'createdAt'));
    }

    #[Group('useDb')]
    public function testProcessRejectsContactWithoutViewPermission(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, false);

        $target = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->insertContact(StrTestHelper::generateUuid(), (string) $target, 'denied@example.com', '2026-08-17 10:00:00');
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListContactsInterface::class)->process(
            new ListContactsInput($principalIdentifier, $target, null),
            new ListContactsOutput(),
        );
    }

    #[Group('useDb')]
    public function testProcessReturnsAllContactsIncludingAnonymousContactsForAdminWhenTargetIdentityIsNotSpecified(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $targetIdentityIdentifier = StrTestHelper::generateUuid();
        $identityContact = StrTestHelper::generateUuid();
        $anonymousContact = StrTestHelper::generateUuid();
        $this->insertContact($identityContact, $targetIdentityIdentifier, 'identity@example.com', '2026-08-15 10:00:00');
        $this->insertContact($anonymousContact, null, 'anonymous@example.com', '2026-08-16 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principalIdentifier, null, null), $output);

        $this->assertSame([$anonymousContact, $identityContact], array_column($output->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([null, $targetIdentityIdentifier], array_column($output->toArray()['contacts'], 'identityIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessReturnsRequestedContactPageAndPaginationMetadata(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $first = StrTestHelper::generateUuid();
        $second = StrTestHelper::generateUuid();
        $third = StrTestHelper::generateUuid();
        $this->insertContact($first, null, 'first@example.com', '2026-08-15 10:00:00');
        $this->insertContact($second, null, 'second@example.com', '2026-08-16 10:00:00');
        $this->insertContact($third, null, 'third@example.com', '2026-08-17 10:00:00');

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(
            new ListContactsInput($principalIdentifier, null, null, 2, 2),
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
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
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
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principalIdentifier, null, true), $hasReplyOutput);

        $this->assertCount(3, DB::getQueryLog());
        $this->assertSame([$sentContact], array_column($hasReplyOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[$sentReply, $laterReply]], array_column($hasReplyOutput->toArray()['contacts'], 'replyIdentifiers'));

        $hasNoReplyOutput = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principalIdentifier, null, false), $hasNoReplyOutput);

        $this->assertSame([$failedContact, $unrepliedContact], array_column($hasNoReplyOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[], []], array_column($hasNoReplyOutput->toArray()['contacts'], 'replyIdentifiers'));

        $allContactsOutput = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principalIdentifier, null, null), $allContactsOutput);

        $this->assertSame([$sentContact, $failedContact, $unrepliedContact], array_column($allContactsOutput->toArray()['contacts'], 'contactIdentifier'));
        $this->assertSame([[$sentReply, $laterReply], [], []], array_column($allContactsOutput->toArray()['contacts'], 'replyIdentifiers'));
    }

    #[Group('useDb')]
    public function testRealAdminPolicyWithOwnContactDenyRejectsWholePageButAllowsOtherAndAnonymous(): void
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();

        CreateAccount::create('00000000-0000-7000-8000-000000000009');
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $provision = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')), $provision);
        $principal = $provision->principal();
        $this->assertNotNull($principal);
        $identifier = $principal->principalIdentifier();
        $query = $this->app()->make(ListContactsInterface::class);

        $empty = new ListContactsOutput();
        $query->process(new ListContactsInput($identifier, null, null), $empty);
        $this->assertSame([], $empty->toArray()['contacts']);

        $own = StrTestHelper::generateUuid();
        $this->insertContact($own, (string) $identity, 'own@example.com', '2026-08-17 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), 'other@example.com', '2026-08-15 10:00:00');
        $this->insertContact(StrTestHelper::generateUuid(), null, 'anonymous@example.com', '2026-08-16 10:00:00');
        $ownContacts = new ListContactsOutput();
        $query->process(new ListContactsInput($identifier, $identity, null), $ownContacts);
        $this->assertSame([$own], array_column($ownContacts->toArray()['contacts'], 'contactIdentifier'));

        try {
            $query->process(new ListContactsInput($identifier, null, null), new ListContactsOutput());
            $this->fail('General policy must reject a page containing other owners');
        } catch (UnauthorizedException) {
        }
        SiteManagementAuthorization::grantAdministrator($principal);
        $allowed = new ListContactsOutput();
        $query->process(new ListContactsInput($identifier, null, null), $allowed);
        $this->assertCount(3, $allowed->toArray()['contacts']);
        DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([
            ['effect' => 'deny', 'actions' => ['contact:view'], 'resource_types' => ['contact'], 'condition' => 'own_contact'],
        ], JSON_THROW_ON_ERROR)]);

        try {
            $query->process(new ListContactsInput($identifier, null, null), new ListContactsOutput());
            $this->fail('A denied contact must reject the whole page');
        } catch (UnauthorizedException) {
        }
        DB::table('contacts')->where('id', $own)->delete();
        $output = new ListContactsOutput();
        $query->process(new ListContactsInput($identifier, null, null), $output);
        $this->assertCount(2, $output->toArray()['contacts']);
        $this->assertSame(2, $output->toArray()['total']);
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
