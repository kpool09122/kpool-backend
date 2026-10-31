<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact;

use Application\Mail\ContactSubmitterReplyMail;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact\ReplyMyContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact\ReplyMyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailOutput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsOutput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateReplyContact;
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ReplyMyContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app()['config']->set('mail.from.address', 'admin@example.test');
        $this->app()['view']->addLocation(dirname(__DIR__, 7) . '/resources/views');
    }

    public function testOwnerReplyNotifiesAdministratorAndPersistsSuccessfulDelivery(): void
    {
        Mail::fake();
        $principal = $this->principal();
        $contactIdentifier = $this->contact($principal);
        $this->operatorReply($contactIdentifier);
        $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($contactIdentifier, $principal->principalIdentifier(), 'ご回答ありがとうございます。追加で確認したいことがあります。'));

        $reply = DB::table('site_management_contact_replies')->where('contact_id', (string) $contactIdentifier)->where('principal_identifier', (string) $principal->principalIdentifier())->sole();
        $this->assertSame((string) $principal->principalIdentifier(), $reply->principal_identifier);
        $this->assertNotNull($reply->sent_at);
        $this->assertNull($reply->failed_at);
        $this->assertNotNull($reply->created_at);
        $this->assertIsString($reply->to_email);
        $this->assertSame('admin@example.test', $this->app()->make(EncryptionServiceInterface::class)->decrypt($reply->to_email));
        Mail::assertSent(ContactSubmitterReplyMail::class, fn (ContactSubmitterReplyMail $mail): bool => $mail->hasTo('admin@example.test') && ! $mail->hasTo('owner@example.com') && (string) $mail->replyContent === $reply->content);
        Mail::assertSentCount(1);
    }

    public function testOwnerCanPostWithoutWaitingForAnOperatorReply(): void
    {
        Mail::fake();
        $owner = $this->principal();
        $id = $this->contact($owner);
        $query = $this->app()->make(ReplyMyContactInterface::class);
        $query->process(new ReplyMyContactInput($id, $owner->principalIdentifier(), '追加の情報'));
        $query->process(new ReplyMyContactInput($id, $owner->principalIdentifier(), '続き'));
        $this->assertSame([1, 2], DB::table('site_management_contact_replies')->where('contact_id', (string) $id)->orderBy('reply_number')->pluck('reply_number')->all());
        Mail::assertSentCount(2);
    }

    /** @return array<string, array{string}> */
    public static function details(): array
    {
        return ['my' => ['my'], 'management scoped' => ['scoped'], 'management identifier' => ['identifier']];
    }

    #[DataProvider('details')]
    public function testConversationReturnsAllPostsInNumberOrderWithStoredAuthors(string $route): void
    {
        $owner = $this->principal();
        $operator = $this->principal();
        SiteManagementAuthorization::grantOperator($operator);
        $id = $this->contact($owner);
        $encryption = $this->app()->make(EncryptionServiceInterface::class);
        $late = new DateTimeImmutable('2026-08-19 12:00:00');
        $early = new DateTimeImmutable('2026-08-17 12:00:00');
        $first = CreateReplyContact::create($id, new Email('owner@example.com'), $operator->principalIdentifier(), $late, null, $late, '運営回答', $encryption);
        $second = CreateReplyContact::create($id, new Email('admin@example.test'), $owner->principalIdentifier(), null, $early, $early, '通知に失敗した本人返信', $encryption);
        $third = CreateReplyContact::create($id, new Email('owner@example.com'), $operator->principalIdentifier(), null, null, $early, '通知待ちの運営回答', $encryption);
        $other = $this->contact($owner);
        CreateReplyContact::create($other, new Email('owner@example.com'), null, $early, null, $early, '別の問い合わせ', $encryption);
        if ($route === 'my') {
            $output = new GetMyContactDetailOutput();
            $this->app()->make(GetMyContactDetailInterface::class)->process(new GetMyContactDetailInput($owner->principalIdentifier(), $id), $output);
        } else {
            $output = new GetContactDetailOutput();
            $this->app()->make(GetContactDetailInterface::class)->process(new GetContactDetailInput($operator->principalIdentifier(), $route === 'scoped' ? $owner->principalIdentifier() : null, $id), $output);
        }
        $replies = $output->toArray()['replies'];
        $this->assertIsArray($replies);
        $this->assertCount(3, $replies);
        $this->assertSame([1, 2, 3], array_column($replies, 'replyNumber'));
        $this->assertSame(array_map(static fn ($reply): string => (string) $reply->replyIdentifier(), [$first, $second, $third]), array_column($replies, 'replyIdentifier'));
        $this->assertSame([(string) $operator->principalIdentifier(), (string) $owner->principalIdentifier(), (string) $operator->principalIdentifier()], array_column($replies, 'principalIdentifier'));
        $this->assertIsArray($replies[1]);
        $this->assertIsArray($replies[2]);
        $this->assertNull($replies[1]['sentAt']);
        $this->assertNull($replies[2]['sentAt']);
        foreach ($replies as $reply) {
            $this->assertIsArray($reply);
            $this->assertArrayNotHasKey('replyType', $reply);
            $this->assertArrayNotHasKey('toEmail', $reply);
        }
    }

    /** @return array<string, array{string}> */
    public static function rejectedActors(): array
    {
        return array_combine(['other', 'anonymous', 'stopped', 'denied', 'unknown', 'missing'], array_map(static fn (string $case): array => [$case], ['other', 'anonymous', 'stopped', 'denied', 'unknown', 'missing']));
    }

    #[DataProvider('rejectedActors')]
    public function testRejectedActorsCannotPersistOrNotify(string $case): void
    {
        Mail::fake();
        $principal = $this->principal();
        $id = $this->contact($principal);
        $this->operatorReply($id);
        if ($case === 'other' || $case === 'anonymous') {
            DB::table('site_management_contacts')->where('id', (string) $id)->update(['principal_identifier' => $case === 'other' ? StrTestHelper::generateUuid() : null]);
        } elseif ($case === 'stopped') {
            DB::table('site_management_principals')->where('id', (string) $principal->principalIdentifier())->update(['status' => PrincipalStatus::SUSPENDED->value]);
        } elseif ($case === 'denied') {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['contact:view'], 'resource_types' => ['contact'], 'condition' => 'own_contact']], JSON_THROW_ON_ERROR)]);
        }
        $before = DB::table('site_management_contact_replies')->count();

        try {
            $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($case === 'missing' ? new ContactIdentifier(StrTestHelper::generateUuid()) : $id, $case === 'unknown' ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : $principal->principalIdentifier(), '返信'));
            $this->fail('Unauthorized reply must be rejected.');
        } catch (UnauthorizedException|ContactNotFoundException $e) {
            $this->assertInstanceOf($case === 'missing' ? ContactNotFoundException::class : UnauthorizedException::class, $e);
            $this->assertSame($before, DB::table('site_management_contact_replies')->count());
            Mail::assertNothingSent();
        }
    }

    public function testNotificationFailureRetainsSubmitterReplyInRealRepositoryAndHistory(): void
    {
        $principal = $this->principal();
        $id = $this->contact($principal);
        $this->operatorReply($id);
        Mail::shouldReceive('to')->once()->with('admin@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->withArgs(static fn ($mail): bool => $mail instanceof ContactSubmitterReplyMail)->andThrow(new RuntimeException('Simulated mail failure; no SMTP connection'));

        try {
            $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($id, $principal->principalIdentifier(), '通知に失敗しても本文を保持 <>&'));
            $this->fail('Existing delivery-failure exception contract must be preserved.');
        } catch (FailedToSendEmailException) {
            $reply = DB::table('site_management_contact_replies')->where('contact_id', (string) $id)->where('principal_identifier', (string) $principal->principalIdentifier())->sole();
            $this->assertSame((string) $principal->principalIdentifier(), $reply->principal_identifier);
            $this->assertSame('通知に失敗しても本文を保持 <>&', $reply->content);
            $this->assertIsString($reply->to_email);
            $this->assertSame('admin@example.test', $this->app()->make(EncryptionServiceInterface::class)->decrypt($reply->to_email));
            $this->assertNull($reply->sent_at);
            $this->assertNotNull($reply->failed_at);
            $this->assertNotNull($reply->created_at);
            $output = new GetMyContactDetailOutput();
            $this->app()->make(GetMyContactDetailInterface::class)->process(new GetMyContactDetailInput($principal->principalIdentifier(), $id), $output);
            $history = $output->toArray()['replies'];
            $this->assertIsArray($history);
            $this->assertIsArray($history[1]);
            $this->assertIsString($reply->created_at);
            $this->assertSame($reply->id, $history[1]['replyIdentifier']);
            $this->assertNull($history[1]['sentAt']);
            $this->assertSame((new DateTimeImmutable($reply->created_at))->format(DATE_ATOM), $history[1]['createdAt']);
        }
    }

    public function testNotificationTextKeepsLiteralReplyContentSeparateFromOriginalInquiry(): void
    {
        Mail::fake();
        $principal = $this->principal();
        $id = $this->contact($principal);
        $this->operatorReply($id);
        $content = "追加質問 <tag>& \"引用\"\n次の行";
        $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($id, $principal->principalIdentifier(), $content));
        Mail::assertSent(ContactSubmitterReplyMail::class, function (ContactSubmitterReplyMail $mail) use ($content): bool {
            $mail->assertSeeInText($content);
            $mail->assertDontSeeInText('初回のお問い合わせ本文');

            return true;
        });
    }

    public function testCommandsPersistSequentialConversationForEveryListWithoutReplyStatus(): void
    {
        Mail::fake();
        $owner = $this->principal();
        $operator = $this->principal();
        SiteManagementAuthorization::grantOperator($operator);
        $id = $this->contact($owner);
        $operatorReply = $this->app()->make(ReplyContactInterface::class);
        $ownerReply = $this->app()->make(ReplyMyContactInterface::class);

        try {
            $operatorReply->process(new ReplyContactInput($id, $owner->principalIdentifier(), '権限のない運営返信'));
            $this->fail('Owner must not gain management reply permission.');
        } catch (UnauthorizedException) {
            Mail::assertNothingSent();
        }
        $operatorReply->process(new ReplyContactInput($id, $operator->principalIdentifier(), '運営の回答'));
        $ownerReply->process(new ReplyMyContactInput($id, $owner->principalIdentifier(), '追加質問'));
        $operatorReply->process(new ReplyContactInput($id, $operator->principalIdentifier(), '追加回答'));
        $management = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($operator->principalIdentifier(), null, 'replied'), $management);
        $mine = new ListMyContactsOutput();
        $this->app()->make(ListMyContactsInterface::class)->process(new ListMyContactsInput($owner->principalIdentifier()), $mine);
        $expectedAuthors = [(string) $operator->principalIdentifier(), (string) $owner->principalIdentifier(), (string) $operator->principalIdentifier()];
        foreach ([$management->toArray()['contacts'][0], $mine->toArray()[0]] as $contact) {
            $this->assertSame([1, 2, 3], array_column($contact['replies'], 'replyNumber'));
            $this->assertSame($expectedAuthors, array_column($contact['replies'], 'principalIdentifier'));
            $this->assertCount(3, $contact['replyIdentifiers']);
            $this->assertArrayNotHasKey('isPending', $contact);
        }
        $secondId = $this->contact($owner);
        $ownerReply->process(new ReplyMyContactInput($secondId, $owner->principalIdentifier(), '別の問い合わせの返信'));
        $this->assertSame([1], DB::table('site_management_contact_replies')->where('contact_id', (string) $secondId)->pluck('reply_number')->all());
    }

    private function principal(): Principal
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreateAccount::create((string) $account);
        $output = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $output);
        $principal = $output->principal();
        $this->assertNotNull($principal);

        return $principal;
    }

    private function contact(Principal $principal): ContactIdentifier
    {
        $id = new ContactIdentifier(StrTestHelper::generateUuid());
        DB::table('site_management_contacts')->insert(['id' => (string) $id, 'principal_identifier' => (string) $principal->principalIdentifier(), 'category' => 1, 'name' => '山田 花子', 'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt('owner@example.com'), 'content' => '初回のお問い合わせ本文', 'language' => 'ja', 'created_at' => '2026-08-16 10:00:00', 'updated_at' => '2026-08-16 10:00:00']);

        return $id;
    }

    private function operatorReply(ContactIdentifier $contactIdentifier): void
    {
        CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, new DateTimeImmutable('2026-08-17 10:00:00'), null, new DateTimeImmutable('2026-08-17 09:00:00'), '運営からの回答です。', $this->app()->make(EncryptionServiceInterface::class));
    }
}
