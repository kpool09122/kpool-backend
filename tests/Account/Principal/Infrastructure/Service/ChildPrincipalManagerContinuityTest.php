<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Infrastructure\Service\ChildPrincipalManagerContinuity;
use Source\SiteManagement\Principal\Application\Service\ReachablePrincipalGroupManagerInterface as SiteManagementReachablePrincipalGroupManagerInterface;
use Source\Wiki\Principal\Application\Service\ReachablePrincipalGroupManagerInterface as WikiReachablePrincipalGroupManagerInterface;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\TestCase;

#[Group('useDb')]
class ChildPrincipalManagerContinuityTest extends TestCase
{
    public function testChildDependenciesUseOnlyPublicApplicationServices(): void
    {
        $constructor = (new ReflectionClass(ChildPrincipalManagerContinuity::class))->getConstructor();
        self::assertNotNull($constructor);
        $childDependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = (string) $parameter->getType();
            if (str_starts_with($type, 'Source\\Wiki') || str_starts_with($type, 'Source\\SiteManagement')) {
                $childDependencies[] = $type;
            }
        }
        self::assertSame([WikiReachablePrincipalGroupManagerInterface::class, SiteManagementReachablePrincipalGroupManagerInterface::class], $childDependencies);
        self::assertInstanceOf(WikiReachablePrincipalGroupManagerInterface::class, $this->app()->make(WikiReachablePrincipalGroupManagerInterface::class));
        self::assertInstanceOf(SiteManagementReachablePrincipalGroupManagerInterface::class, $this->app()->make(SiteManagementReachablePrincipalGroupManagerInterface::class));
    }

    public function testQueriesDoNotGrowWithAccountMembers(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $service = $this->app()->make(ChildPrincipalManagerContinuity::class);
        DB::enableQueryLog();

        try {
            DB::flushQueryLog();
            $service->assertPreserved($f->account, []);
            $before = count(DB::getQueryLog());
            for ($i = 0; $i < 10; $i++) {
                $f->principal($f->account, 'Additional member ' . $i);
            }
            DB::flushQueryLog();
            $service->assertPreserved($f->account, []);
            self::assertSame($before, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function testAccountStatusFinalStateCannotStrandAllChildManagers(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $principals = [];
        foreach ($repository->findByAccountId($f->account) as $principal) {
            $principal->changeStatus(PrincipalStatus::SUSPENDED);
            $principals[(string) $principal->principalIdentifier()] = $principal;
        }
        $this->expectException(CannotRemoveLastPrincipalGroupManagerException::class);
        $this->app()->make(ChildPrincipalManagerContinuity::class)->assertPreserved($f->account, $principals);
    }

    public function testFinalStateKeepsAnotherReachableActiveManager(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        DB::table('account_principals')->where('identity_id', (string) $f->administrator->identityIdentifier())->update(['status' => 'forced_stop']);
        $this->app()->make(ChildPrincipalManagerContinuity::class)->assertPreserved($f->account, []);
        self::assertSame(1, DB::table('account_principals')->where('status', 'forced_stop')->count());
    }
}
