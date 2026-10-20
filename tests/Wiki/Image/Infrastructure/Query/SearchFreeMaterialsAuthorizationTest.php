<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Image\Application\Service\FreeMaterialSearchServiceInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsOutput;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SearchFreeMaterialsAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Operator', 'SeniorCollaborator', 'Administrator', 'AgencyActor', 'TalentActor', 'Collaborator', 'None'] as $role) {
            yield $role => [$role, in_array($role, ['Operator', 'SeniorCollaborator'], true)];
        }
    }

    #[DataProvider('roles')]
    public function testSeededRoleAuthorizationBeforeSearch(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityId);
        CreateAccount::create((string) $accountId);
        CreatePrincipal::create($principalId, $identityId, $accountId);
        CreatePrincipalGroup::create($groupId, $accountId);
        CreatePrincipalGroupMembership::create((string) $groupId, (string) $principalId);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);
        $freeMaterialSearchService = Mockery::mock(FreeMaterialSearchServiceInterface::class);
        if ($allowed) {
            $freeMaterialSearchService->shouldReceive('search')->once()->andReturn([]);
        } else {
            $freeMaterialSearchService->shouldNotReceive('search');
            $this->expectException(DisallowedException::class);
        }
        $this->app()->instance(FreeMaterialSearchServiceInterface::class, $freeMaterialSearchService);
        $output = new SearchFreeMaterialsOutput();
        $this->app()->make(SearchFreeMaterialsInterface::class)->process(new SearchFreeMaterialsInput($principalId, 'Edited title', null, ResourceType::GROUP, Language::ENGLISH, []), $output);
        self::assertSame(['sites' => []], $output->toArray());
    }
}
