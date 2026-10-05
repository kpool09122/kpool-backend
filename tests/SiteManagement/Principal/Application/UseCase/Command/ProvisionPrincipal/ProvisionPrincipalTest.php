<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipal;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class ProvisionPrincipalTest extends TestCase
{
    public function testNewPrincipalGetsGeneralMembership(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findByIdentityId')->willReturn(null);
        $repository->expects(self::once())->method('save')->with($principal);
        $factory = $this->createMock(PrincipalFactoryInterface::class);
        $factory->expects(self::once())->method('create')->with($identity)->willReturn($principal);
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(PrincipalGroup::GENERAL), 'General', []);
        $groups->method('findById')->willReturn($group);
        $groups->expects(self::once())->method('save')->with($group);
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($repository, $factory, $groups))->process(new ProvisionPrincipalInput($identity), $output);
        self::assertSame($principal, $output->principal());
        self::assertSame([$principal->principalIdentifier()], array_values($group->members()));
    }

    public function testExistingPrincipalIsReturnedWithoutCreatingOrChangingMembership(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findByIdentityId')->willReturn($principal);
        $repository->expects(self::never())->method('save');
        $factory = $this->createMock(PrincipalFactoryInterface::class);
        $factory->expects(self::never())->method('create');
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $groups->expects(self::never())->method('save');
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($repository, $factory, $groups))->process(new ProvisionPrincipalInput($identity), $output);
        self::assertSame($principal, $output->principal());
    }
}
