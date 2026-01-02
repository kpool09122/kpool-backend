<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole\ChangePrincipalRole;
use Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole\ChangePrincipalRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole\ChangePrincipalRoleInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\DisallowedChangeRoleException;
use Source\Wiki\Principal\Domain\Exception\OperatorNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ChangePrincipalRoleTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changePrincipalRole = $this->app->make(ChangePrincipalRoleInterface::class);
        $this->assertInstanceOf(ChangePrincipalRole::class, $changePrincipalRole);
    }

    /**
     * 正常系：正しくPrincipal Entityが更新されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws OperatorNotFoundException
     * @throws DisallowedChangeRoleException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $operatorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $oldRole = Role::COLLABORATOR;
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangePrincipalRoleInput(
            $operatorIdentifier,
            $targetPrincipalIdentifier,
            $targetRole,
        );

        $operator = new Principal(
            $operatorIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $targetPrincipal = new Principal(
            $targetPrincipalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $oldRole,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($operatorIdentifier)
            ->andReturn($operator);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($targetPrincipalIdentifier)
            ->andReturn($targetPrincipal);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($targetPrincipal)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changePrincipalRole = $this->app->make(ChangePrincipalRoleInterface::class);
        $result = $changePrincipalRole->process($input);

        $this->assertSame((string)$targetPrincipalIdentifier, (string)$result->principalIdentifier());
        $this->assertSame($targetRole, $result->role());
        $this->assertNull($result->agencyId());
        $this->assertEmpty($result->groupIds());
        $this->assertEmpty($result->talentIds());
    }

    /**
     * 異常系：管理者以外はRoleの変更ができず、例外が発生すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws OperatorNotFoundException
     * @throws DisallowedChangeRoleException
     * @throws PrincipalNotFoundException
     */
    public function testDisallowedOperator(): void
    {
        $operatorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangePrincipalRoleInput(
            $operatorIdentifier,
            $targetPrincipalIdentifier,
            $targetRole,
        );

        $operator = new Principal(
            $operatorIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::COLLABORATOR,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($operatorIdentifier)
            ->andReturn($operator);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changePrincipalRole = $this->app->make(ChangePrincipalRoleInterface::class);

        $this->expectException(DisallowedChangeRoleException::class);
        $changePrincipalRole->process($input);
    }

    /**
     * 異常系：操作者が見つからない場合は、例外が発生すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws OperatorNotFoundException
     * @throws DisallowedChangeRoleException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundOperator(): void
    {
        $operatorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangePrincipalRoleInput(
            $operatorIdentifier,
            $targetPrincipalIdentifier,
            $targetRole,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($operatorIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changePrincipalRole = $this->app->make(ChangePrincipalRoleInterface::class);

        $this->expectException(OperatorNotFoundException::class);
        $changePrincipalRole->process($input);
    }

    /**
     * 異常系：Roleを変更する対象のPrincipalが見つからない場合は、例外が発生すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws OperatorNotFoundException
     * @throws DisallowedChangeRoleException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundTargetPrincipal(): void
    {
        $operatorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangePrincipalRoleInput(
            $operatorIdentifier,
            $targetPrincipalIdentifier,
            $targetRole,
        );

        $operator = new Principal(
            $operatorIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($operatorIdentifier)
            ->andReturn($operator);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($targetPrincipalIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changePrincipalRole = $this->app->make(ChangePrincipalRoleInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $changePrincipalRole->process($input);
    }
}
