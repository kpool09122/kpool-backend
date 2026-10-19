<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Infrastructure\Service\PrincipalGroupChangeGuard;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\PrincipalStatusBulkFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalGroupChangeGuardTest extends TestCase
{
    /** @return array<string, array{bool}> */
    public static function paths(): array
    {
        return ['remove member' => [false], 'delete group' => [true]];
    }

    #[DataProvider('paths')]
    public function testExistingPathsCannotCountForcedStopAsRemainingManager(bool $delete): void
    {
        $f = new PrincipalStatusBulkFixture('wiki');
        $repository = $this->app()->make(PrincipalGroupRepositoryInterface::class);
        $group = $repository->findById(new PrincipalGroupIdentifier($f->group));
        self::assertNotNull($group);
        $other = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $f->base->account, 'Stopped administrator', false, new DateTimeImmutable(), $group->roles());
        $other->addMember(new PrincipalIdentifier($f->forced));
        $repository->save($other);
        $this->app()->make(PrincipalGroupChangeGuard::class)->assertPreserved($f->base->account, [$group, $other]);

        try {
            if ($delete) {
                $this->app()->make(DeletePrincipalGroup::class)->process(new DeletePrincipalGroupInput($group->principalGroupIdentifier()));
            } else {
                $this->app()->make(RemovePrincipalFromPrincipalGroup::class)->process(new RemovePrincipalFromPrincipalGroupInput($group->principalGroupIdentifier(), new PrincipalIdentifier($f->manager)), new RemovePrincipalFromPrincipalGroupOutput());
            }
            self::fail('The last effective active manager must survive.');
        } catch (CannotRemoveLastWikiAdministratorException) {
            self::assertNotNull($repository->findById($group->principalGroupIdentifier()));
            self::assertTrue($repository->findById($group->principalGroupIdentifier())->hasMember(new PrincipalIdentifier($f->manager)));
        }
    }
}
