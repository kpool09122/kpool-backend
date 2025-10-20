<?php

declare(strict_types=1);

namespace Tests\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\AccessControl\Application\Exception\ActorNotFoundException;
use Source\Wiki\AccessControl\Application\Exception\UnauthorizedChangingACException;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControl;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInput;
use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInterface;
use Source\Wiki\AccessControl\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ChangeAccessControlTest extends TestCase
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
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);
        $this->assertInstanceOf(ChangeAccessControl::class, $changeAccessControl);
    }

    /**
     * 正常系：正しくActor Entityが更新されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ActorNotFoundException
     */
    public function testProcess(): void
    {
        $holdingRole = Role::ADMINISTRATOR;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $oldRole = Role::COLLABORATOR;
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $principalIdentifier,
            $targetRole,
        );

        $principal = new Principal(
            $principalIdentifier,
            $oldRole,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($principal)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);
        $principal = $changeAccessControl->process($input);

        $this->assertSame((string)$principalIdentifier, (string)$principal->principalIdentifier());
        $this->assertSame($targetRole, $principal->role());
        $this->assertNull($principal->agencyId());
        $this->assertEmpty($principal->groupIds());
        $this->assertEmpty($principal->talentIds());
    }

    /**
     * 異常系：管理者以外はRoleの変更ができず、例外が発生すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ActorNotFoundException
     */
    public function testUnauthorizedActor(): void
    {
        $holdingRole = Role::COLLABORATOR;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $principalIdentifier,
            $targetRole,
        );
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);

        $this->expectException(UnauthorizedChangingACException::class);
        $changeAccessControl->process($input);
    }

    /**
     * 異常系：Roleを変更するActorが見つからない場合は、例外が発生すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenNotFoundActor(): void
    {
        $holdingRole = Role::ADMINISTRATOR;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $principalIdentifier,
            $targetRole,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);

        $this->expectException(ActorNotFoundException::class);
        $changeAccessControl->process($input);
    }
}
