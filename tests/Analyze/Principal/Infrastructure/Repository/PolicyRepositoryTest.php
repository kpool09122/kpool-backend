<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Infrastructure\Repository;

use LogicException;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\ConditionInterface;
use Source\Analyze\Principal\Domain\ValueObject\Effect;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;
use Source\Analyze\Principal\Domain\ValueObject\Statement;
use Source\Analyze\Principal\Infrastructure\Repository\PolicyRepository;
use Tests\TestCase;

#[Group('useDb')]
class PolicyRepositoryTest extends TestCase
{
    public function testSavingConditionsDoesNotSilentlyDiscardThem(): void
    {
        $id = new PolicyIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'original', [], null));
        $condition = $this->createStub(ConditionInterface::class);
        $statement = new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS], $condition);

        try {
            $repository->save(new Policy($id, 'conditional', [$statement], null));
            self::fail('Conditional policies must not be persisted without their conditions.');
        } catch (LogicException) {
            self::assertSame('original', $repository->findByIds([$id])[0]->name());
        }
    }

    public function testSaveAndLoadRoundTrip(): void
    {
        $id = new PolicyIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'test', [], null));
        $entities = $repository->findByIds([$id]);
        self::assertCount(1, $entities);
        $entity = $entities[0];
        self::assertSame([], $repository->findByIds([]));
        self::assertSame('test', $entity->name());
    }
}
