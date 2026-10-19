<?php

declare(strict_types=1);

namespace Tests;

use DomainException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException as AccountPrincipalNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\CannotRemoveLastAdministratorException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException as SitePrincipalNotFoundException;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException as WikiPrincipalNotFoundException;
use Tests\Helper\PrincipalStatusBulkFixture;
use Tests\Helper\StrTestHelper;

#[Group('useDb')]
class PrincipalStatusBulkTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function contexts(): iterable
    {
        foreach (['account', 'wiki', 'site'] as $context) {
            yield $context => [$context];
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidStatusIdentifiers(): iterable
    {
        foreach (['account', 'wiki', 'site'] as $context) {
            foreach (['duplicate', 'foreignAccount', 'nonexistent'] as $invalid) {
                yield $context . ' ' . $invalid => [$context, $invalid];
            }
        }
    }

    #[DataProvider('invalidStatusIdentifiers')]
    public function testInvalidStatusIdentifierRejectsSiblingStatusAndGroupChanges(string $context, string $invalid): void
    {
        $f = new PrincipalStatusBulkFixture($context);
        $beforeStatuses = DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all();
        $beforeMembers = DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all();
        $invalidId = match ($invalid) {
            'duplicate' => $f->successor,
            'foreignAccount' => $f->foreign,
            default => StrTestHelper::generateUuid(),
        };
        $expectedException = $invalid === 'duplicate' ? InvalidArgumentException::class : match ($context) {
            'account' => AccountPrincipalNotFoundException::class,
            'wiki' => WikiPrincipalNotFoundException::class,
            default => SitePrincipalNotFoundException::class,
        };

        try {
            $f->saveEntries([
                ['principalIdentifier' => $f->successor, 'status' => 'active'],
                ['principalIdentifier' => $invalidId, 'status' => 'suspended'],
            ], [$f->manager, $f->successor]);
            self::fail('Invalid status identifiers must reject the whole update.');
        } catch (InvalidArgumentException|AccountPrincipalNotFoundException|WikiPrincipalNotFoundException|SitePrincipalNotFoundException $exception) {
            self::assertInstanceOf($expectedException, $exception);
            if ($invalid === 'duplicate') {
                self::assertSame('Duplicate principal status identifier.', $exception->getMessage());
            }
            self::assertSame($beforeStatuses, DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all());
            self::assertEquals($beforeMembers, DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all());
        }
    }

    #[DataProvider('contexts')]
    public function testAtomicManagerHandoverUsesFinalMembershipAndStatuses(string $context): void
    {
        $f = new PrincipalStatusBulkFixture($context);
        $result = $f->save([$f->manager => 'suspended', $f->successor => 'active', $f->forced => 'forced_stop'], [$f->successor]);
        self::assertCount(3, $result['principalStatuses']);
        self::assertSame('suspended', DB::table($f->prefix . '_principals')->where('id', $f->manager)->value('status'));
        self::assertSame('active', DB::table($f->prefix . '_principals')->where('id', $f->successor)->value('status'));
        self::assertSame('forced_stop', DB::table($f->prefix . '_principals')->where('id', $f->forced)->value('status'));
        self::assertSame([$f->successor], DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->pluck('principal_id')->all());
    }

    #[DataProvider('contexts')]
    public function testUnchangedForcedStopDoesNotPreventOtherEdits(string $context): void
    {
        $f = new PrincipalStatusBulkFixture($context);
        $before = DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all();
        $result = $f->save([$f->successor => 'active', $f->forced => 'forced_stop']);
        self::assertCount(2, $result['principalStatuses']);
        self::assertSame([], $result['principalGroups']);
        self::assertEquals($before, DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all());
        $result = $f->save([$f->successor => 'suspended', $f->forced => 'forced_stop'], [$f->manager]);
        self::assertCount(2, $result['principalStatuses']);
        self::assertCount(1, $result['principalGroups']);
    }

    #[DataProvider('contexts')]
    public function testLastActiveManagerCannotBeReplacedByStoppedOrForcedStopMember(string $context): void
    {
        foreach (['suspended', 'forced_stop'] as $remaining) {
            $f = new PrincipalStatusBulkFixture($context);
            $candidate = $remaining === 'forced_stop' ? $f->forced : $f->successor;
            $membersBefore = DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->pluck('principal_id')->all();

            try {
                $f->save([$f->manager => 'suspended'], [$candidate]);
                self::fail('No effective active manager remains.');
            } catch (CannotRemoveLastPrincipalGroupManagerException|CannotRemoveLastAdministratorException|CannotRemoveLastWikiAdministratorException) {
                self::assertSame('active', DB::table($f->prefix . '_principals')->where('id', $f->manager)->value('status'));
                self::assertSame($membersBefore, DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->pluck('principal_id')->all());
            }
        }
    }

    #[DataProvider('contexts')]
    public function testForcedStopTransitionRollsBackEveryStatusAndMembership(string $context): void
    {
        foreach (['active', 'suspended'] as $attempt) {
            $f = new PrincipalStatusBulkFixture($context);
            $before = DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all();

            try {
                $f->save([$f->successor => 'active', $f->forced => $attempt], [$f->successor]);
                self::fail('Forced stop cannot be changed by user management.');
            } catch (DomainException) {
                self::assertSame('suspended', DB::table($f->prefix . '_principals')->where('id', $f->successor)->value('status'));
                self::assertSame('forced_stop', DB::table($f->prefix . '_principals')->where('id', $f->forced)->value('status'));
                self::assertEquals($before, DB::table($f->prefix . '_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->all());
            }
        }
    }

    #[DataProvider('contexts')]
    public function testLaterPersistenceFailureRollsBackAlreadyWrittenMembershipAndStatus(string $context): void
    {
        $f = new PrincipalStatusBulkFixture($context);
        $beforeStatuses = DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all();
        $beforeMembers = DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->orderBy('principal_id')->pluck('principal_id')->all();
        $writes = 0;
        $observedPartialWrite = false;
        DB::listen(function (QueryExecuted $query) use ($f, &$writes, &$observedPartialWrite): void {
            if (! str_starts_with(strtolower($query->sql), 'update ') || ! str_contains($query->sql, $f->prefix . '_principals')) {
                return;
            }
            $writes++;
            if ($writes === 1) {
                self::assertSame('suspended', DB::table($f->prefix . '_principals')->where('id', $f->manager)->value('status'));
                self::assertSame([$f->successor], DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->pluck('principal_id')->all());
                $observedPartialWrite = true;
            }
            if ($writes === 2) {
                throw new RuntimeException('injected_later_persistence_failure');
            }
        });

        try {
            $f->save([$f->manager => 'suspended', $f->successor => 'active'], [$f->successor]);
            self::fail('The later repository write must fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('injected_later_persistence_failure', $exception->getMessage());
        }
        self::assertTrue($observedPartialWrite);
        self::assertSame(2, $writes);
        self::assertSame($beforeStatuses, DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all());
        self::assertSame($beforeMembers, DB::table($f->prefix . '_principal_group_memberships')->where('principal_group_id', $f->group)->orderBy('principal_id')->pluck('principal_id')->all());
    }

    #[DataProvider('contexts')]
    public function testGroupOnlyUpdateDoesNotTouchOmittedStatuses(string $context): void
    {
        $f = new PrincipalStatusBulkFixture($context);
        $before = DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all();
        $result = $f->save([], [$f->manager, $f->forced]);
        self::assertSame([], $result['principalStatuses']);
        self::assertSame($before, DB::table($f->prefix . '_principals')->orderBy('id')->pluck('status', 'id')->all());
    }
}
