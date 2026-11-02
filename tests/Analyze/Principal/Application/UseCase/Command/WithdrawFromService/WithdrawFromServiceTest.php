<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\WithdrawFromService;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class WithdrawFromServiceTest extends TestCase
{
    public function testWithdrawalDeletesExistingPrincipal(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'), PrincipalStatus::ACTIVE);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->expects(self::once())->method('findAllByIdentityIdentifier')->with($identity)->willReturn([$principal]);
        $repository->expects(self::once())->method('delete')->with($principal);
        (new WithdrawFromService($repository))->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
    }

    public function testWithdrawalWithoutPrincipalIsIdempotent(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'), PrincipalStatus::ACTIVE);
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findAllByIdentityIdentifier')->willReturn([]);
        $repository->expects(self::never())->method('delete');
        (new WithdrawFromService($repository))->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
    }
}
