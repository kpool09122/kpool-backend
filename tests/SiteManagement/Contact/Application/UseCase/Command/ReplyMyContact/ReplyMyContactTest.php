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
use Source\SiteManagement\Contact\Domain\Exception\ContactNotRepliedByOperatorException;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyType;
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

        $reply = DB::table('contact_replies')->where('contact_id', (string) $contactIdentifier)->where('reply_type', 'submitter')->sole();
        $this->assertSame((string) $principal->principalIdentifier(), $reply->principal_identifier);
        $this->assertNotNull($reply->sent_at);
        $this->assertNull($reply->failed_at);
        $this->assertNotNull($reply->created_at);
        $this->assertIsString($reply->to_email);
        $this->assertSame('admin@example.test', $this->app()->make(EncryptionServiceInterface::class)->decrypt($reply->to_email));
        Mail::assertSent(ContactSubmitterReplyMail::class, fn (ContactSubmitterReplyMail $mail): bool => $mail->hasTo('admin@example.test') && ! $mail->hasTo('owner@example.com') && (string) $mail->replyContent === $reply->content);
        Mail::assertSentCount(1);
    }

    /** @return array<string, array{?string, bool, bool}> */
    public static function ineligibleReplies(): array
    {
        return [
            'no history' => [null, false, false],
            'failed operator' => ['operator', false, true],
            'pending operator' => ['operator', false, false],
            'sent and failed operator' => ['operator', true, true],
            'sent submitter is not an operator' => ['submitter', true, false],
        ];
    }

    #[DataProvider('ineligibleReplies')]
    public function testRequiresSuccessfullySentOperatorHistory(?string $type, bool $sent, bool $failed): void
    {
        Mail::fake();
        $principal = $this->principal();
        $contactIdentifier = $this->contact($principal);
        if ($type !== null) {
            $at = new DateTimeImmutable('2026-08-17 10:00:00');
            CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, $sent ? $at : null, $failed ? $at : null, $at, '履歴', $this->app()->make(EncryptionServiceInterface::class), ReplyType::from($type));
        }
        $before = DB::table('contact_replies')->count();

        try {
            $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($contactIdentifier, $principal->principalIdentifier(), '追加の質問'));
            $this->fail('Replying before a successful operator reply must be rejected.');
        } catch (ContactNotRepliedByOperatorException) {
            $this->assertSame($before, DB::table('contact_replies')->count());
            Mail::assertNothingSent();
        }
    }

    /** @return array<string, array{string}> */
    public static function details(): array
    {
        return ['my' => ['my'], 'management scoped' => ['scoped'], 'management identifier' => ['identifier']];
    }

    #[DataProvider('details')]
    public function testConversationIncludesFailedSubmitterAndOrdersByVisibleTimestampWithoutLeakingAuthors(string $route): void
    {
        $principal = $this->principal();
        $contactIdentifier = $this->contact($principal);
        if ($route !== 'my') {
            SiteManagementAuthorization::grantOperator($principal);
        }
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $operatorCreated = new DateTimeImmutable('2026-08-17 09:00:00');
        $operatorSent = new DateTimeImmutable('2026-08-17 11:00:00');
        $submitterCreated = new DateTimeImmutable('2026-08-17 10:00:00');
        $operator = CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), $principal->principalIdentifier(), $operatorSent, null, $operatorCreated, '遅れて正常送信された運営返信', $encryptionService);
        $submitter = CreateReplyContact::create($contactIdentifier, new Email('admin@example.test'), $principal->principalIdentifier(), null, $submitterCreated, $submitterCreated, '通知失敗でも表示する本人返信', $encryptionService, ReplyType::SUBMITTER);
        CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, null, $submitterCreated, $submitterCreated, '非表示の運営失敗', $encryptionService);
        CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, null, null, $submitterCreated, '非表示の運営未送信', $encryptionService);
        DB::flushQueryLog();
        DB::enableQueryLog();
        if ($route === 'my') {
            $output = new GetMyContactDetailOutput();
            $this->app()->make(GetMyContactDetailInterface::class)->process(new GetMyContactDetailInput($principal->principalIdentifier(), $contactIdentifier), $output);
        } elseif ($route === 'scoped') {
            $output = new GetContactDetailOutput();
            $this->app()->make(GetContactDetailInterface::class)->process(new GetContactDetailInput($principal->principalIdentifier(), $principal->principalIdentifier(), $contactIdentifier), $output);
        } else {
            $output = new GetContactDetailOutput();
            $this->app()->make(GetContactDetailInterface::class)->process(new GetContactDetailInput($principal->principalIdentifier(), null, $contactIdentifier), $output);
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $replies = $output->toArray()['replies'];
        $this->assertIsArray($replies);
        $this->assertSame([(string) $submitter->replyIdentifier(), (string) $operator->replyIdentifier()], array_column($replies, 'replyIdentifier'));
        $this->assertSame(['submitter', 'operator'], array_column($replies, 'replyType'));
        $this->assertIsArray($replies[0]);
        $this->assertIsArray($replies[1]);
        $this->assertNull($replies[0]['sentAt']);
        $this->assertSame($submitterCreated->format(DATE_ATOM), $replies[0]['createdAt']);
        $this->assertSame($operatorSent->format(DATE_ATOM), $replies[1]['sentAt']);
        $this->assertSame($operatorCreated->format(DATE_ATOM), $replies[1]['createdAt']);
        foreach ($replies as $reply) {
            $this->assertIsArray($reply);
            $this->assertArrayNotHasKey('toEmail', $reply);
            if ($route === 'my') {
                $this->assertArrayNotHasKey('principalIdentifier', $reply);
                $this->assertArrayNotHasKey('name', $reply);
            } else {
                $this->assertSame((string) $principal->principalIdentifier(), $reply['principalIdentifier']);
                $this->assertSame('test-identity', $reply['name']);
            }
        }
        $this->assertCount(1, array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'from "contact_replies"')));
        $this->assertCount($route === 'my' ? 0 : 1, array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'from "identities"')));
    }

    /** @return array<string, array{?string, ?string, bool, bool}> */
    public static function pendingStates(): array
    {
        return [
            'initial inquiry' => [null, null, false, true],
            'operator success' => ['2026-08-17 11:00:00', null, false, false],
            'submitter after operator' => ['2026-08-17 11:00:00', '2026-08-17 12:00:00', false, true],
            'failed submitter after operator' => ['2026-08-17 11:00:00', '2026-08-17 12:00:00', true, true],
            'operator after submitter' => ['2026-08-17 13:00:00', '2026-08-17 12:00:00', false, false],
            'equal timestamp later submitter is pending' => ['2026-08-17 12:00:00', '2026-08-17 12:00:00', false, true],
            'submitter alone not operator reply' => [null, '2026-08-17 12:00:00', false, true],
        ];
    }

    #[DataProvider('pendingStates')]
    public function testPendingFilterAndReplyIdentifiersKeepDistinctMeanings(?string $operatorAt, ?string $submitterAt, bool $submitterFailed, bool $pending): void
    {
        $principal = $this->principal();
        SiteManagementAuthorization::grantOperator($principal);
        $contactIdentifier = $this->contact($principal);
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $operator = $operatorAt === null ? null : CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, new DateTimeImmutable($operatorAt), null, new DateTimeImmutable('2026-08-17 09:00:00'), '運営回答', $encryptionService);
        if ($submitterAt !== null) {
            $at = new DateTimeImmutable($submitterAt);
            CreateReplyContact::create($contactIdentifier, new Email('admin@example.test'), $principal->principalIdentifier(), $submitterFailed ? null : $at, $submitterFailed ? $at : null, $at, '本人返信', $encryptionService, ReplyType::SUBMITTER);
        }
        // A later operator failure must never mark the conversation resolved.
        $late = new DateTimeImmutable('2026-08-18 12:00:00');
        CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, null, $late, $late, '運営送信失敗', $encryptionService);
        $output = new ListContactsOutput();
        $query = $this->app()->make(ListContactsInterface::class);
        $query->process(new ListContactsInput($principal->principalIdentifier(), null, null), $output);
        $contact = $output->toArray()['contacts'][0];
        $this->assertArrayHasKey('isPending', $contact);
        $this->assertSame($pending, $contact['isPending'] ?? null);
        $this->assertSame($operator === null ? [] : [(string) $operator->replyIdentifier()], $output->toArray()['contacts'][0]['replyIdentifiers']);
        foreach ([true, false] as $condition) {
            $query->process(new ListContactsInput($principal->principalIdentifier(), null, null, isPending: $condition), $output);
            $this->assertSame($pending === $condition ? 1 : 0, $output->toArray()['total']);
            $query->process(new ListContactsInput($principal->principalIdentifier(), null, $operator !== null, isPending: $condition), $output);
            $this->assertSame($pending === $condition ? 1 : 0, $output->toArray()['total']);
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
            DB::table('contacts')->where('id', (string) $id)->update(['principal_identifier' => $case === 'other' ? StrTestHelper::generateUuid() : null]);
        } elseif ($case === 'stopped') {
            DB::table('site_management_principals')->where('id', (string) $principal->principalIdentifier())->update(['status' => PrincipalStatus::SUSPENDED->value]);
        } elseif ($case === 'denied') {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['contact:view'], 'resource_types' => ['contact'], 'condition' => 'own_contact']], JSON_THROW_ON_ERROR)]);
        }
        $before = DB::table('contact_replies')->count();

        try {
            $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($case === 'missing' ? new ContactIdentifier(StrTestHelper::generateUuid()) : $id, $case === 'unknown' ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : $principal->principalIdentifier(), '返信'));
            $this->fail('Unauthorized reply must be rejected.');
        } catch (UnauthorizedException|ContactNotFoundException $e) {
            $this->assertInstanceOf($case === 'missing' ? ContactNotFoundException::class : UnauthorizedException::class, $e);
            $this->assertSame($before, DB::table('contact_replies')->count());
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
            $reply = DB::table('contact_replies')->where('contact_id', (string) $id)->where('reply_type', 'submitter')->sole();
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

    /** @return array<string, array{bool}> */
    public static function sameSecondOrders(): array
    {
        return ['operator then submitter' => [true], 'submitter then operator' => [false]];
    }

    #[DataProvider('sameSecondOrders')]
    public function testSameSecondRepliesUseIdentifierOrderForPendingAndConversation(bool $operatorFirst): void
    {
        $principal = $this->principal();
        SiteManagementAuthorization::grantOperator($principal);
        $id = $this->contact($principal);
        $at = new DateTimeImmutable('2026-08-17 12:00:00');
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $operator = CreateReplyContact::create($id, new Email('owner@example.com'), null, $at, null, $at, '運営回答', $encryptionService);
        $submitter = CreateReplyContact::create($id, new Email('admin@example.test'), $principal->principalIdentifier(), $at, null, $at, '本人返信', $encryptionService, ReplyType::SUBMITTER);
        $earlier = '01900000-0000-7000-8000-000000000001';
        $later = '01900000-0000-7000-8000-000000000002';
        DB::table('contact_replies')->where('id', (string) $operator->replyIdentifier())->update(['id' => $operatorFirst ? $earlier : $later]);
        DB::table('contact_replies')->where('id', (string) $submitter->replyIdentifier())->update(['id' => $operatorFirst ? $later : $earlier]);

        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principal->principalIdentifier(), null, null), $output);
        $contact = $output->toArray()['contacts'][0];
        $this->assertArrayHasKey('isPending', $contact);
        $this->assertSame($operatorFirst, $contact['isPending'] ?? null);
        foreach ([true, false] as $pending) {
            $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principal->principalIdentifier(), null, null, isPending: $pending), $output);
            $this->assertSame($operatorFirst === $pending ? 1 : 0, $output->toArray()['total']);
        }
        $detail = new GetMyContactDetailOutput();
        $this->app()->make(GetMyContactDetailInterface::class)->process(new GetMyContactDetailInput($principal->principalIdentifier(), $id), $detail);
        $replies = $detail->toArray()['replies'];
        $this->assertIsArray($replies);
        $this->assertSame([$earlier, $later], array_column($replies, 'replyIdentifier'));
        $this->assertSame($operatorFirst ? ['operator', 'submitter'] : ['submitter', 'operator'], array_column($replies, 'replyType'));
    }

    public function testRealCommandsTransitionPendingWithoutGrantingOperatorPermissionToSubmitter(): void
    {
        Mail::fake();
        $owner = $this->principal();
        $operator = $this->principal();
        SiteManagementAuthorization::grantOperator($operator);
        $id = $this->contact($owner);
        $list = $this->app()->make(ListContactsInterface::class);
        $output = new ListContactsOutput();
        $list->process(new ListContactsInput($operator->principalIdentifier(), null, null), $output);
        $this->assertTrue($output->toArray()['contacts'][0]['isPending'] ?? null);

        try {
            $this->app()->make(ReplyContactInterface::class)->process(new ReplyContactInput($id, $owner->principalIdentifier(), '本人は運営経路を使えない'));
            $this->fail('General must not receive operator reply permission.');
        } catch (UnauthorizedException) {
            $this->assertSame(0, DB::table('contact_replies')->count());
            Mail::assertNothingSent();
        }
        $operatorReply = $this->app()->make(ReplyContactInterface::class);
        $operatorReply->process(new ReplyContactInput($id, $operator->principalIdentifier(), '初回の運営回答'));
        $list->process(new ListContactsInput($operator->principalIdentifier(), null, null), $output);
        $this->assertFalse($output->toArray()['contacts'][0]['isPending'] ?? null);
        $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($id, $owner->principalIdentifier(), '本人から追加質問'));
        $list->process(new ListContactsInput($operator->principalIdentifier(), null, null), $output);
        $this->assertTrue($output->toArray()['contacts'][0]['isPending'] ?? null);
        $this->assertCount(1, $output->toArray()['contacts'][0]['replyIdentifiers']);
        $operatorReply->process(new ReplyContactInput($id, $operator->principalIdentifier(), '追加質問への運営回答'));
        $list->process(new ListContactsInput($operator->principalIdentifier(), null, null), $output);
        $this->assertFalse($output->toArray()['contacts'][0]['isPending'] ?? null);
        $this->assertCount(2, $output->toArray()['contacts'][0]['replyIdentifiers']);
        $this->app()->make(ReplyMyContactInterface::class)->process(new ReplyMyContactInput($id, $owner->principalIdentifier(), '本人から再質問'));
        Mail::shouldReceive('to')->once()->with('owner@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Simulated operator delivery failure'));

        try {
            $operatorReply->process(new ReplyContactInput($id, $operator->principalIdentifier(), '失敗した運営回答'));
            $this->fail('Operator delivery failure must keep its existing exception.');
        } catch (FailedToSendEmailException) {
            $list->process(new ListContactsInput($operator->principalIdentifier(), null, null), $output);
            $this->assertTrue($output->toArray()['contacts'][0]['isPending'] ?? null);
            $this->assertCount(2, $output->toArray()['contacts'][0]['replyIdentifiers']);
            $this->assertSame(1, DB::table('contact_replies')->where('reply_type', 'operator')->whereNotNull('failed_at')->count());
        }
        $myOutput = new ListMyContactsOutput();
        $this->app()->make(ListMyContactsInterface::class)->process(new ListMyContactsInput($owner->principalIdentifier()), $myOutput);
        $this->assertSame($output->toArray()['contacts'][0]['replyIdentifiers'], $myOutput->toArray()[0]['replyIdentifiers']);
    }

    public function testPendingFilterRunsBeforePaginationWithBoundedQueries(): void
    {
        $principal = $this->principal();
        SiteManagementAuthorization::grantOperator($principal);
        $pendingIdentifiers = [];
        for ($index = 0; $index < 9; ++$index) {
            $id = $this->contact($principal);
            if ($index % 2 === 0) {
                $this->operatorReply($id);
            } else {
                $pendingIdentifiers[] = (string) $id;
            }
        }
        rsort($pendingIdentifiers);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $output = new ListContactsOutput();
        $this->app()->make(ListContactsInterface::class)->process(new ListContactsInput($principal->principalIdentifier(), $principal->principalIdentifier(), false, 2, 2, true), $output);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $page = $output->toArray();
        $this->assertSame(4, $page['total']);
        $this->assertSame(2, $page['last_page']);
        $this->assertSame(2, $page['current_page']);
        $this->assertSame(array_slice($pendingIdentifiers, 2, 2), array_column($page['contacts'], 'contactIdentifier'));
        $contactQueries = array_values(array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'from "contacts"') || str_contains($query['query'], 'from "contact_replies"')));
        $this->assertCount(3, $contactQueries);
        $this->assertStringContainsString('not exists', $contactQueries[0]['query']);
        $this->assertStringContainsString('limit 2 offset 2', $contactQueries[1]['query']);
        foreach ($page['contacts'] as $contact) {
            $this->assertTrue($contact['isPending'] ?? null);
            $this->assertSame([], $contact['replyIdentifiers']);
        }
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
        DB::table('contacts')->insert(['id' => (string) $id, 'principal_identifier' => (string) $principal->principalIdentifier(), 'category' => 1, 'name' => '山田 花子', 'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt('owner@example.com'), 'content' => '初回のお問い合わせ本文', 'language' => 'ja', 'created_at' => '2026-08-16 10:00:00', 'updated_at' => '2026-08-16 10:00:00']);

        return $id;
    }

    private function operatorReply(ContactIdentifier $contactIdentifier): void
    {
        CreateReplyContact::create($contactIdentifier, new Email('owner@example.com'), null, new DateTimeImmutable('2026-08-17 10:00:00'), null, new DateTimeImmutable('2026-08-17 09:00:00'), '運営からの回答です。', $this->app()->make(EncryptionServiceInterface::class));
    }
}
