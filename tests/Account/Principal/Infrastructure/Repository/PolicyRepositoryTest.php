<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Principal\Infrastructure\Repository\PolicyRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app->make(PolicyRepositoryInterface::class);

        $this->assertInstanceOf(PolicyRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testSaveAndFindByIds(): void
    {
        $policy = new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'ACCOUNT_INVITATION_CREATE_TEST',
            [new Statement(
                Effect::ALLOW,
                [Action::INVITATION_CREATE],
                [ResourceType::ACCOUNT],
            )],
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $repository->save($policy);

        $this->assertDatabaseHas('account_policies', [
            'id' => (string) $policy->policyIdentifier(),
            'name' => 'ACCOUNT_INVITATION_CREATE_TEST',
            'is_system_policy' => true,
        ]);

        $foundPolicies = $repository->findByIds([$policy->policyIdentifier()]);

        $this->assertCount(1, $foundPolicies);
        $foundPolicy = $foundPolicies[(string) $policy->policyIdentifier()];
        $this->assertSame('ACCOUNT_INVITATION_CREATE_TEST', $foundPolicy->name());
        $this->assertSame(Effect::ALLOW, $foundPolicy->statements()[0]->effect());
        $this->assertSame(Action::INVITATION_CREATE, $foundPolicy->statements()[0]->actions()[0]);
        $this->assertSame(ResourceType::ACCOUNT, $foundPolicy->statements()[0]->resourceTypes()[0]);
    }
}
