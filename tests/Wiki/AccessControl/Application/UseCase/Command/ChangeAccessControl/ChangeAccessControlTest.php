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
use Source\Wiki\AccessControl\Domain\Repository\ActorRepositoryInterface;
use Source\Wiki\Shared\Domain\Entity\Actor;
use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;
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
        $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
        $this->app->instance(ActorRepositoryInterface::class, $actorRepository);
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
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $oldRole = Role::COLLABORATOR;
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $actorIdentifier,
            $targetRole,
        );

        $actor = new Actor(
            $actorIdentifier,
            $oldRole,
            null,
            [],
            null,
        );

        $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
        $actorRepository->shouldReceive('findById')
            ->once()
            ->with($actorIdentifier)
            ->andReturn($actor);
        $actorRepository->shouldReceive('save')
            ->once()
            ->with($actor)
            ->andReturn(null);

        $this->app->instance(ActorRepositoryInterface::class, $actorRepository);
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);
        $actor = $changeAccessControl->process($input);

        $this->assertSame((string)$actorIdentifier, (string)$actor->actorIdentifier());
        $this->assertSame($targetRole, $actor->role());
        $this->assertNull($actor->agencyId());
        $this->assertEmpty($actor->groupIds());
        $this->assertNull($actor->memberId());
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
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $actorIdentifier,
            $targetRole,
        );
        $actorRepository = Mockery::mock(ActorRepositoryInterface::class);

        $this->app->instance(ActorRepositoryInterface::class, $actorRepository);
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
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::ADMINISTRATOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $actorIdentifier,
            $targetRole,
        );

        $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
        $actorRepository->shouldReceive('findById')
            ->once()
            ->with($actorIdentifier)
            ->andReturn(null);

        $this->app->instance(ActorRepositoryInterface::class, $actorRepository);
        $changeAccessControl = $this->app->make(ChangeAccessControlInterface::class);

        $this->expectException(ActorNotFoundException::class);
        $changeAccessControl->process($input);
    }
}
