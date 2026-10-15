<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Query;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateReplyContact;
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetContactDetailTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsSpecifiedPrincipalContactForOperator(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        $target = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = StrTestHelper::generateUuid();
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $this->insertContact($contactIdentifier, (string) $target);

        $output = new GetContactDetailOutput();
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, $target, new ContactIdentifier($contactIdentifier)),
            $output,
        );

        $this->assertSame($contactIdentifier, $output->toArray()['contactIdentifier']);
    }

    #[Group('useDb')]
    public function testProcessRejectsNonOperator(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, false);

        $this->expectException(UnauthorizedException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, new PrincipalIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier(StrTestHelper::generateUuid())),
            new GetContactDetailOutput(),
        );
    }

    #[Group('useDb')]
    public function testProcessRejectsContactNotOwnedBySpecifiedPrincipal(): void
    {
        $requester = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($requester);
        $principalIdentifier = SiteManagementAuthorization::bind($requester, true);
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, StrTestHelper::generateUuid());

        $this->expectException(ContactNotFoundException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principalIdentifier, new PrincipalIdentifier(StrTestHelper::generateUuid()), new ContactIdentifier($contactIdentifier)),
            new GetContactDetailOutput(),
        );
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    public static function policyScenarios(): array
    {
        return [
            'general can view own contact' => [true, false, false, true],
            'general cannot view another contact' => [false, false, false, false],
            'own contact deny overrides Operator allow' => [true, true, true, false],
            'own contact deny does not affect another contact' => [false, true, true, true],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('policyScenarios')]
    public function testProcessEvaluatesPoliciesForTheTargetContact(bool $ownContact, bool $operator, bool $denyOwnContact, bool $allowed): void
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $provision = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $provision);
        $principal = $provision->principal();
        $this->assertNotNull($principal);
        if ($operator) {
            SiteManagementAuthorization::grantOperator($principal);
        }
        if ($denyOwnContact) {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update([
                'statements' => json_encode([[
                    'effect' => 'deny',
                    'actions' => ['contact:view'],
                    'resource_types' => ['contact'],
                    'condition' => 'own_contact',
                ]], JSON_THROW_ON_ERROR),
            ]);
        }
        $owner = $ownContact ? $principal->principalIdentifier() : new PrincipalIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = StrTestHelper::generateUuid();
        $this->insertContact($contactIdentifier, (string) $owner);
        if (! $allowed) {
            $this->expectException(UnauthorizedException::class);
        }
        $output = new GetContactDetailOutput();
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principal->principalIdentifier(), $owner, new ContactIdentifier($contactIdentifier)),
            $output,
        );
        if ($allowed) {
            $this->assertSame($contactIdentifier, $output->toArray()['contactIdentifier']);
        }
    }

    #[Group('useDb')]
    #[DataProvider('policyScenarios')]
    public function testSpecifiedTargetPreservesPolicyDenialBeforeMissingContact(bool $ownContact, bool $operator, bool $denyOwnContact, bool $allowed): void
    {
        $principal = $this->provisionPrincipal();
        if ($operator) {
            SiteManagementAuthorization::grantOperator($principal);
        }
        if ($denyOwnContact) {
            $this->setGeneralPolicy('deny-own');
        }
        $owner = $ownContact ? $principal->principalIdentifier() : new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->expectException($allowed ? ContactNotFoundException::class : UnauthorizedException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principal->principalIdentifier(), $owner, new ContactIdentifier(StrTestHelper::generateUuid())),
            new GetContactDetailOutput(),
        );
    }

    #[Group('useDb')]
    public function testSpecifiedTargetDoesNotMatchAnonymousContact(): void
    {
        $principal = $this->provisionPrincipal();
        SiteManagementAuthorization::grantOperator($principal);
        $id = StrTestHelper::generateUuid();
        $this->insertContact($id, null);
        $this->expectException(ContactNotFoundException::class);
        $this->app()->make(GetContactDetailInterface::class)->process(
            new GetContactDetailInput($principal->principalIdentifier(), $principal->principalIdentifier(), new ContactIdentifier($id)),
            new GetContactDetailOutput(),
        );
    }

    /** @return array<string, array{bool}> */
    public static function owners(): array
    {
        return ['signed-in submitter' => [true], 'anonymous submitter' => [false]];
    }

    #[Group('useDb')]
    #[DataProvider('owners')]
    public function testReturnsExistingDetailContractWithOnlySuccessfullySentRepliesWithoutTarget(bool $signedIn): void
    {
        $principal = $this->provisionPrincipal();
        SiteManagementAuthorization::grantOperator($principal);
        $owner = $signedIn ? StrTestHelper::generateUuid() : null;
        $id = StrTestHelper::generateUuid();
        $this->insertContact($id, $owner);
        $encryptionService = $this->app()->make(EncryptionServiceInterface::class);
        $replies = [];
        // Insert out of order, including a tied creation time, to exercise both sort keys.
        foreach ([['2026-08-18 09:00:00', true, false], ['2026-08-17 09:00:00', true, false], ['2026-08-17 09:00:00', true, false], ['2026-08-19 09:00:00', false, true], ['2026-08-20 09:00:00', false, false], ['2026-08-21 09:00:00', true, true]] as [$createdAt, $sent, $failed]) {
            $reply = CreateReplyContact::create(
                new ContactIdentifier($id),
                new Email('contact@example.com'),
                $principal->principalIdentifier(),
                $sent ? new DateTimeImmutable($createdAt) : null,
                $failed ? new DateTimeImmutable($createdAt) : null,
                new DateTimeImmutable($createdAt),
                '返信内容',
                $encryptionService,
            );
            if ($sent && ! $failed) {
                $replies[] = $reply;
            }
        }
        usort($replies, static fn ($a, $b): int => $a->createdAt() <=> $b->createdAt() ?: strcmp((string) $a->replyIdentifier(), (string) $b->replyIdentifier()));
        $otherId = StrTestHelper::generateUuid();
        $this->insertContact($otherId, null);
        CreateReplyContact::create(new ContactIdentifier($otherId), new Email('contact@example.com'), null, new DateTimeImmutable('2026-08-17 09:00:00'), null, new DateTimeImmutable('2026-08-17 09:00:00'), '別の問い合わせへの返信', $encryptionService);

        DB::enableQueryLog();
        $output = $this->processWithoutTarget($principal->principalIdentifier(), $id);
        $contactQueries = array_values(array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'from "contacts"') || str_contains($query['query'], 'from "contact_replies"')));
        DB::disableQueryLog();
        $this->assertCount(2, $contactQueries);
        foreach ($contactQueries as $query) {
            $this->assertStringNotContainsString('email', $query['query']);
        }
        $this->assertStringContainsString('limit 1', $contactQueries[0]['query']);

        $this->assertSame([
            'contactIdentifier' => $id,
            'principalIdentifier' => $owner,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ者',
            'createdAt' => (new DateTimeImmutable('2026-08-16 10:00:00'))->format(DateTimeInterface::ATOM),
            'content' => 'お問い合わせ内容',
            'replies' => array_map(static fn ($reply): array => [
                'replyIdentifier' => (string) $reply->replyIdentifier(),
                'content' => (string) $reply->content(),
                'sentAt' => $reply->sentAt()?->format(DateTimeInterface::ATOM),
            ], $replies),
        ], $output->toArray());
    }

    /** @return array<string, array{string, bool, ?string, bool}> */
    public static function policiesWithoutTarget(): array
    {
        return [
            'Operator own' => ['own', true, null, true],
            'Operator another' => ['another', true, null, true],
            'Operator anonymous' => ['anonymous', true, null, true],
            'General own is not Operator' => ['own', false, null, false],
            'General another' => ['another', false, null, false],
            'General anonymous' => ['anonymous', false, null, false],
            'non-Operator with unconditional view permission' => ['own', false, 'allow', false],
            'explicit own deny overrides Operator allow' => ['own', true, 'deny-own', false],
            'own deny does not affect another owner' => ['another', true, 'deny-own', true],
            'own deny does not match null owner' => ['anonymous', true, 'deny-own', true],
            'unconditional deny blocks anonymous' => ['anonymous', true, 'deny', false],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('policiesWithoutTarget')]
    public function testRequiresOperatorAndEvaluatesPersistedOwnerWithoutTarget(string $ownerKind, bool $operator, ?string $policy, bool $allowed): void
    {
        $principal = $this->provisionPrincipal();
        if ($operator) {
            SiteManagementAuthorization::grantOperator($principal);
        }
        if ($policy !== null) {
            $this->setGeneralPolicy($policy);
        }
        $owner = match ($ownerKind) {
            'own' => (string) $principal->principalIdentifier(),
            'another' => StrTestHelper::generateUuid(),
            default => null,
        };
        $id = StrTestHelper::generateUuid();
        $this->insertContact($id, $owner);
        if (! $allowed) {
            $this->expectException(UnauthorizedException::class);
        }
        $output = $this->processWithoutTarget($principal->principalIdentifier(), $id);
        if ($allowed) {
            $this->assertSame($owner, $output->toArray()['principalIdentifier']);
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function conditionalAllows(): array
    {
        return ['own contact' => ['own', true], 'another owner' => ['another', false], 'anonymous owner' => ['anonymous', false], 'missing contact' => ['missing', false]];
    }

    #[Group('useDb')]
    #[DataProvider('conditionalAllows')]
    public function testOperatorWithOnlyOwnContactAllowUsesActualOwnerWithoutTarget(string $ownerKind, bool $allowed): void
    {
        $principal = $this->provisionPrincipal();
        SiteManagementAuthorization::grantOperator($principal);
        DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => '[]']);
        DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::OPERATOR_ROLE)->update([
            'statements' => json_encode([[
                'effect' => 'allow',
                'actions' => ['contact:view'],
                'resource_types' => ['contact'],
                'condition' => 'own_contact',
            ]], JSON_THROW_ON_ERROR),
        ]);
        $id = StrTestHelper::generateUuid();
        if ($ownerKind !== 'missing') {
            $this->insertContact($id, match ($ownerKind) {
                'own' => (string) $principal->principalIdentifier(),
                'another' => StrTestHelper::generateUuid(),
                default => null,
            });
        }
        if (! $allowed) {
            $this->expectException(UnauthorizedException::class);
        }
        $output = $this->processWithoutTarget($principal->principalIdentifier(), $id);
        if ($allowed) {
            $this->assertSame((string) $principal->principalIdentifier(), $output->toArray()['principalIdentifier']);
        }
    }

    #[Group('useDb')]
    public function testMissingContactReturnsNotFoundForAuthorizedOperatorWithoutTarget(): void
    {
        $principal = $this->provisionPrincipal();
        SiteManagementAuthorization::grantOperator($principal);
        $this->expectException(ContactNotFoundException::class);
        $this->processWithoutTarget($principal->principalIdentifier(), StrTestHelper::generateUuid());
    }

    #[Group('useDb')]
    public function testMissingContactDoesNotBypassPermissionDenialWithoutTarget(): void
    {
        $principal = $this->provisionPrincipal();
        SiteManagementAuthorization::grantOperator($principal);
        $this->setGeneralPolicy('deny');
        $this->expectException(UnauthorizedException::class);
        $this->processWithoutTarget($principal->principalIdentifier(), StrTestHelper::generateUuid());
    }

    #[Group('useDb')]
    public function testOperatorRoleFromAnotherAccountDoesNotQualifyWithoutTarget(): void
    {
        $principal = $this->provisionPrincipal();
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        $group = $this->app()->make(PrincipalGroupFactoryInterface::class)->create('Operator', [new RoleIdentifier(SiteManagementAuthorizationSeeder::OPERATOR_ROLE)], $otherAccount);
        $group->addMember($principal->principalIdentifier());
        $this->app()->make(PrincipalGroupRepositoryInterface::class)->save($group);
        $this->setGeneralPolicy('allow');
        $id = StrTestHelper::generateUuid();
        $this->insertContact($id, null);
        $this->expectException(UnauthorizedException::class);
        $this->processWithoutTarget($principal->principalIdentifier(), $id);
    }

    public function testMissingRequesterPrincipalIsUnauthorizedBeforeContactLookupWithoutTarget(): void
    {
        $identifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($identifier)->andReturnNull();
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->expectException(UnauthorizedException::class);
        $this->processWithoutTarget($identifier, StrTestHelper::generateUuid());
    }

    private function processWithoutTarget(PrincipalIdentifier $principalIdentifier, string $id): GetContactDetailOutput
    {
        $output = new GetContactDetailOutput();
        $this->app()->make(GetContactDetailInterface::class)->process(new GetContactDetailInput($principalIdentifier, null, new ContactIdentifier($id)), $output);

        return $output;
    }

    private function provisionPrincipal(): Principal
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $output = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $output);
        $principal = $output->principal();
        $this->assertNotNull($principal);

        return $principal;
    }

    private function setGeneralPolicy(string $policy): void
    {
        DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update([
            'statements' => json_encode([[
                'effect' => $policy === 'allow' ? 'allow' : 'deny',
                'actions' => ['contact:view'],
                'resource_types' => ['contact'],
                'condition' => $policy === 'deny-own' ? 'own_contact' : null,
            ]], JSON_THROW_ON_ERROR),
        ]);
    }

    private function insertContact(string $id, ?string $principalIdentifier): void
    {
        DB::table('contacts')->insert(['id' => $id, 'principal_identifier' => $principalIdentifier, 'category' => Category::SUGGESTIONS->value, 'name' => '問い合わせ者', 'email' => $this->app()->make(EncryptionServiceInterface::class)->encrypt('contact@example.com'), 'content' => 'お問い合わせ内容', 'language' => 'ja', 'created_at' => '2026-08-16 10:00:00', 'updated_at' => '2026-08-16 10:00:00']);
    }
}
