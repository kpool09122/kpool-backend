<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\AutomaticCreateDraftGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\AutomaticCreateDraftGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\AutomaticDraftGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupSource;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftGroupTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithAdministrator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::ADMINISTRATOR);
        $draftGroup = $this->makeDraftGroup();

        $service = Mockery::mock(AutomaticDraftGroupCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftGroup);

        $repository = Mockery::mock(GroupRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftGroup)
            ->andReturn(null);

        $this->app->instance(AutomaticDraftGroupCreationServiceInterface::class, $service);
        $this->app->instance(GroupRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftGroupInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftGroupInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftGroup, $result);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::SENIOR_COLLABORATOR);
        $draftGroup = $this->makeDraftGroup();

        $service = Mockery::mock(AutomaticDraftGroupCreationServiceInterface::class);
        $service->shouldReceive('create')
            ->once()
            ->with($payload, $principal)
            ->andReturn($draftGroup);

        $repository = Mockery::mock(GroupRepositoryInterface::class);
        $repository->shouldReceive('saveDraft')
            ->once()
            ->with($draftGroup)
            ->andReturn(null);

        $this->app->instance(AutomaticDraftGroupCreationServiceInterface::class, $service);
        $this->app->instance(GroupRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftGroupInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftGroupInterface::class);

        $result = $useCase->process($input);
        $this->assertSame($draftGroup, $result);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $payload = $this->makePayload();
        $principal = $this->makePrincipal(Role::AGENCY_ACTOR);

        $service = Mockery::mock(AutomaticDraftGroupCreationServiceInterface::class);
        $repository = Mockery::mock(GroupRepositoryInterface::class);

        $this->app->instance(AutomaticDraftGroupCreationServiceInterface::class, $service);
        $this->app->instance(GroupRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftGroupInput($payload, $principal);
        $useCase = $this->app->make(AutomaticCreateDraftGroupInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    private function makePayload(): AutomaticDraftGroupCreationPayload
    {
        return new AutomaticDraftGroupCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('auto generated group profile'),
            [
                new SongIdentifier(StrTestHelper::generateUlid()),
                new SongIdentifier(StrTestHelper::generateUlid()),
            ],
            new AutomaticDraftGroupSource('news::12345'),
        );
    }

    private function makePrincipal(Role $role): Principal
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        return new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), $role, null, [], []);
    }

    private function makeDraftGroup(): DraftGroup
    {
        return new DraftGroup(
            new GroupIdentifier(StrTestHelper::generateUlid()),
            null,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('auto generated group'),
            [
                new SongIdentifier(StrTestHelper::generateUlid()),
                new SongIdentifier(StrTestHelper::generateUlid()),
            ],
            null,
            ApprovalStatus::Pending,
        );
    }
}
