<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Statement;

class MonetizationAuthorizationSeeder extends Seeder
{
    public function __construct(
        private readonly PolicyRepositoryInterface $policyRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function run(): void
    {
        $read = new PolicyIdentifier('09290000-0000-7000-8000-000000000001');
        $manage = new PolicyIdentifier('09290000-0000-7000-8000-000000000002');
        $this->policyRepository->save(new Policy($read, 'PRINCIPAL_GROUP_READ', [
            new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]),
        ], null));
        $this->policyRepository->save(new Policy($manage, 'PRINCIPAL_GROUP_MANAGE', [
            new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP]),
        ], null));
        $this->roleRepository->save(new Role(new RoleIdentifier('09290000-0000-7000-8000-000000000003'), Role::OWNER, [$read, $manage], null));
        $this->roleRepository->save(new Role(new RoleIdentifier('09290000-0000-7000-8000-000000000004'), Role::GENERAL, [$read], null));
    }
}
