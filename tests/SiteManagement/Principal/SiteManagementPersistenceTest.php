<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipal;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SiteManagementPersistenceTest extends TestCase
{
    public function testNewPrincipalDefaultsGeneralAndOwnershipIsEnforced(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $output = new ProvisionPrincipalOutput();
        $useCase = $this->app()->make(ProvisionPrincipal::class);
        $useCase->process(new ProvisionPrincipalInput($identity), $output);
        $useCase->process(new ProvisionPrincipalInput($identity), $output);
        $principal = $output->principal();
        self::assertNotNull($principal);
        self::assertSame((string) $principal->principalIdentifier(), (string) $repository->findByIdentityId($identity)?->principalIdentifier());
        $evaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        self::assertTrue($evaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, $identity)));
        self::assertFalse($evaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT, new IdentityIdentifier(StrTestHelper::generateUuid()))));
        self::assertFalse($evaluator->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT)));
        self::assertFalse($evaluator->evaluate($principal, Action::CONTACT_REPLY, new Resource(ResourceType::CONTACT, $identity)));
        (new SiteManagementAuthorizationSeeder())->run();
        self::assertSame(1, DB::table('site_management_principal_group_memberships')->where('principal_id', (string) $principal->principalIdentifier())->count());
        $repository->delete($principal);
        self::assertSame(0, DB::table('site_management_principal_group_memberships')->where('principal_id', (string) $principal->principalIdentifier())->count());
        self::assertSame(2, DB::table('site_management_policies')->count());
    }

    public function testLegacyUpgradePreservesIdsBindingsPermissionsAndHistory(): void
    {
        $migration = require __DIR__.'/../../../database/migrations/2026_10_05_000000_create_site_management_authorization.php';
        assert(is_object($migration) && method_exists($migration, 'up') && method_exists($migration, 'down'));
        $migration->down();
        $identities = [];
        $principals = [];
        foreach (['admin','none'] as $index => $role) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => 'legacy'.$index.'@example.com']);
            $id = StrTestHelper::generateUuid();
            $identities[] = $identity;
            $principals[] = $id;
            DB::table('site_management_users')->insert(['id' => $id,'identity_id' => (string)$identity,'role' => $role,'created_at' => now(),'updated_at' => now()]);
        }
        $contact = StrTestHelper::generateUuid();
        DB::table('contacts')->insert(['id' => $contact,'identity_identifier' => (string)$identities[0],'category' => 1,'name' => 'legacy','email' => 'encrypted','content' => 'history','language' => 'ja','created_at' => now(),'updated_at' => now()]);
        $reply = StrTestHelper::generateUuid();
        DB::table('contact_replies')->insert(['id' => $reply,'contact_id' => $contact,'identity_identifier' => (string)$identities[0],'content' => 'reply','to_email' => 'encrypted','created_at' => now(),'updated_at' => now()]);
        $migration->up();
        $repository = $this->app()->make(PrincipalRepositoryInterface::class);
        $evaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        foreach ($identities as $index => $identity) {
            $principal = $repository->findByIdentityId($identity);
            self::assertNotNull($principal);
            self::assertSame($principals[$index], (string)$principal->principalIdentifier());
            self::assertSame($index === 0, $evaluator->evaluate($principal, Action::CONTACT_REPLY, new Resource(ResourceType::CONTACT)));
        }
        $this->assertDatabaseHas('contacts', ['id' => $contact,'content' => 'history']);
        $this->assertDatabaseHas('contact_replies', ['id' => $reply,'content' => 'reply']);
    }
}
