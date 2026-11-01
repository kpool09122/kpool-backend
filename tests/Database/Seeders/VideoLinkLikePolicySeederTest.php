<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\TestCase;

#[Group('useDb')]
class VideoLinkLikePolicySeederTest extends TestCase
{
    public function testRerunPreservesExistingIdsRepairsAttachmentsAndDoesNotDuplicatePoliciesOrRoles(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $policies = DB::table('wiki_policies')->orderBy('name')->pluck('id', 'name')->all();
        $roles = DB::table('wiki_roles')->orderBy('name')->pluck('id', 'name')->all();
        $attachments = DB::table('wiki_role_policy_attachments')->count();
        DB::table('wiki_role_policy_attachments')->where('policy_id', $policies['GLOBAL_LIKE_VIDEO_LINK'])->delete();
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        self::assertSame($policies, DB::table('wiki_policies')->orderBy('name')->pluck('id', 'name')->all());
        self::assertSame($roles, DB::table('wiki_roles')->orderBy('name')->pluck('id', 'name')->all());
        self::assertSame($attachments, DB::table('wiki_role_policy_attachments')->count());
        $policyRepository = $this->app()->make(PolicyRepositoryInterface::class);
        foreach ([Action::LIKE_VIDEO_LINK, Action::UNLIKE_VIDEO_LINK] as $action) {
            $name = 'GLOBAL_' . strtoupper($action->value);
            $policy = $policyRepository->findSystemByName($name);
            self::assertNotNull($policy);
            self::assertCount(1, $policy->statements());
            $statement = $policy->statements()[0];
            self::assertSame([$action], $statement->actions());
            self::assertSame([ResourceType::AGENCY, ResourceType::GROUP, ResourceType::SONG, ResourceType::TALENT], $statement->resourceTypes());
            foreach (['Collaborator', 'Operator'] as $role) {
                self::assertSame(1, DB::table('wiki_role_policy_attachments')->where('role_id', $roles[$role])->where('policy_id', $policies[$name])->count());
            }
            foreach (['SeniorCollaborator', 'AgencyActor', 'TalentActor', 'None', 'Administrator'] as $role) {
                self::assertSame(0, DB::table('wiki_role_policy_attachments')->where('role_id', $roles[$role])->where('policy_id', $policies[$name])->count());
            }
        }
    }
}
