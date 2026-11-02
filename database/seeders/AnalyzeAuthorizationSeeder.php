<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\Effect;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\Statement;

class AnalyzeAuthorizationSeeder extends Seeder
{
    public function __construct(private readonly PolicyRepositoryInterface $policyRepository, private readonly RoleRepositoryInterface $roleRepository)
    {
    }

    public function run(): void
    {
        $policyIdentifier = new PolicyIdentifier('08370000-0000-7000-8000-000000000001');
        $this->policyRepository->save(new Policy($policyIdentifier, 'GLOBAL_ANALYSIS_VIEW', [new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS])], null));
        $this->roleRepository->save(new Role(new RoleIdentifier('08370000-0000-7000-8000-000000000002'), 'General', [$policyIdentifier], null));
    }
}
