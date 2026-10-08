<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SiteManagementPersistenceTest extends TestCase
{
    public function testPrincipalsAndPermissionsAreScopedToAccount(): void
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();

        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $firstAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $secondAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $firstAccount);
        CreateAccount::create((string) $secondAccount, ['email' => 'second@example.com']);
        $first = $this->provision($identity, $firstAccount);
        $second = $this->provision($identity, $secondAccount);
        $again = $this->provision($identity, $firstAccount);
        $this->assertSame((string) $first->principalIdentifier(), (string) $again->principalIdentifier());
        $this->assertNotSame((string) $first->principalIdentifier(), (string) $second->principalIdentifier());
        $this->assertDatabaseCount('site_management_principal_groups', 2);
        SiteManagementAuthorization::grantAdministrator($first);

        $evaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        $resource = new Resource(ResourceType::CONTACT, $first->principalIdentifier());
        $this->assertTrue($evaluator->evaluate($first, Action::CONTACT_REPLY, $resource));
        $this->assertFalse($evaluator->evaluate($second, Action::CONTACT_REPLY, $resource));
        $this->assertFalse($evaluator->evaluate($second, Action::CONTACT_VIEW, $resource));
        $this->assertTrue($evaluator->evaluate($second, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $second->principalIdentifier())));
        $this->assertFalse($evaluator->evaluate($second, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, new PrincipalIdentifier(StrTestHelper::generateUuid()))));

        // Even an invalid cross-account membership must not grant the other Account's permissions.
        $adminGroupId = DB::table('site_management_principal_groups')->where('account_id', (string) $firstAccount)->where('name', 'administrator')->value('id');
        DB::table('site_management_principal_group_memberships')->insert(['principal_id' => (string) $second->principalIdentifier(), 'principal_group_id' => $adminGroupId]);
        $this->assertFalse($evaluator->evaluate($second, Action::CONTACT_REPLY, $resource));

        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $this->assertCount(2, $repository->findAllByIdentityIdentifier($identity));
        $this->assertSame((string) $first->principalIdentifier(), (string) $repository->findByIdentityIdentifierAndAccountIdentifier($identity, $firstAccount)?->principalIdentifier());
    }

    private function provision(IdentityIdentifier $identity, AccountIdentifier $account): Principal
    {
        $output = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $output);
        $principal = $output->principal();
        $this->assertNotNull($principal);

        return $principal;
    }
}
