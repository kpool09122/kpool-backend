<?php

declare(strict_types=1);

namespace Tests\Helper;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

final class SiteManagementAuthorization
{
    public static function bind(IdentityIdentifier $identityIdentifier, bool $allowed = true): PrincipalIdentifier
    {
        $identifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($identifier, $identityIdentifier, new AccountIdentifier(StrTestHelper::generateUuid()));
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findById')->with($identifier)->andReturn($principal);
        $evaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $evaluator->shouldReceive('evaluate')->andReturn($allowed);
        app()->instance(PrincipalRepositoryInterface::class, $repository);
        app()->instance(PolicyEvaluatorInterface::class, $evaluator);

        return $identifier;
    }

    public static function grantOperator(Principal $principal): void
    {
        $group = app(PrincipalGroupFactoryInterface::class)->create(
            'Operator',
            [new RoleIdentifier(SiteManagementAuthorizationSeeder::OPERATOR_ROLE)],
            $principal->accountIdentifier(),
        );
        $group->addMember($principal->principalIdentifier());
        app(PrincipalGroupRepositoryInterface::class)->save($group);
    }
}
