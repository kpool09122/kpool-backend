<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PolicyRepository;
use Tests\TestCase;

#[Group('useDb')]
class PolicyRepositoryTest extends TestCase
{
    public function testSaveAndLoadRoundTrip(): void
    {
        $id = new PolicyIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'test', []));
        $entities = $repository->findByIds([$id]);
        self::assertCount(1, $entities);
        $entity = $entities[0];
        self::assertSame([], $repository->findByIds([]));
        self::assertSame('test', $entity->name());
    }
}
