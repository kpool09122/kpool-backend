<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class WithdrawFromServiceTest extends TestCase
{
    public function testWithdrawalDeletesExistingPrincipal(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->expects(self::once())->method('findByIdentityId')->with($identity)->willReturn($principal);
        $repository->expects(self::once())->method('delete')->with($principal);
        (new WithdrawFromService($repository))->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
    }

    public function testWithdrawalWithoutPrincipalIsIdempotent(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findByIdentityId')->willReturn(null);
        $repository->expects(self::never())->method('delete');
        (new WithdrawFromService($repository))->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
    }
}
