<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\SiteManagement\Principal\Application\Service\ReachablePrincipalGroupManagerInterface;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\TestCase;

#[Group('useDb')]
class ReachablePrincipalGroupManagerTest extends TestCase
{
    public function testUsesProposedIdentityReachabilityWithoutWrites(): void
    {
        $fixture = new SiteManagementPrincipalGroupsFixture();
        $reachablePrincipalGroupManager = $this->app()->make(ReachablePrincipalGroupManagerInterface::class);
        $before = DB::table('site_management_principals')->get()->toArray();
        self::assertFalse($reachablePrincipalGroupManager->isPreserved($fixture->account, []));
        self::assertFalse($reachablePrincipalGroupManager->isPreserved($fixture->account, [$fixture->foreign->identityIdentifier()]));
        self::assertTrue($reachablePrincipalGroupManager->isPreserved($fixture->account, [$fixture->administrator->identityIdentifier()]));
        self::assertTrue($reachablePrincipalGroupManager->isPreserved($fixture->otherAccount, []));
        self::assertEquals($before, DB::table('site_management_principals')->get()->toArray());
    }
}
