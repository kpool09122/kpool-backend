<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroup;
use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\PublishGroup\PublishGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $groupService = Mockery::mock(GroupServiceInterface::class);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $groupSnapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $groupSnapshotFactory);
        $groupSnapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $groupSnapshotRepository);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $this->assertInstanceOf(PublishGroup::class, $publishGroup);
    }

    /**
     * 正常系：正しく変更されたGroupが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            hasPublishedGroup: true,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishGroup->publishedGroupIdentifier)
            ->andReturn($dummyPublishGroup->publishedGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->publishedGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        // スナップショット関連のモック（既存の公開済みGroupがある場合はスナップショットを保存）
        $groupSnapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $groupSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->publishedGroup)
            ->andReturn($dummyPublishGroup->snapshot);

        $groupSnapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $groupSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->snapshot)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $groupSnapshotFactory);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $groupSnapshotRepository);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishedGroup = $publishGroup->process($input);
        $this->assertSame((string)$dummyPublishGroup->publishedGroupIdentifier, (string)$publishedGroup->groupIdentifier());
        $this->assertSame($dummyPublishGroup->language->value, $publishedGroup->language()->value);
        $this->assertSame((string)$dummyPublishGroup->agencyIdentifier, (string)$publishedGroup->agencyIdentifier());
        $this->assertSame((string)$dummyPublishGroup->name, (string)$publishedGroup->name());
        $this->assertSame((string)$dummyPublishGroup->normalizedName, (string)$publishedGroup->normalizedName());
        $this->assertSame((string)$dummyPublishGroup->description, (string)$publishedGroup->description());
        $this->assertSame($dummyPublishGroup->songIdentifiers, $publishedGroup->songIdentifiers());
        $this->assertSame((string)$dummyPublishGroup->imagePath, (string)$publishedGroup->imagePath());
        $this->assertSame($dummyPublishGroup->publishedVersion->value() + 1, $publishedGroup->version()->value());
    }

    /**
     * 正常系：正しく変更されたGroupが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessForTheFirstTime(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            hasPublishedGroup: false,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        // スナップショット関連のモック（初回公開時はスナップショットを保存しない）
        $groupSnapshotFactory = Mockery::mock(GroupSnapshotFactoryInterface::class);
        $groupSnapshotFactory->shouldNotReceive('create');

        $groupSnapshotRepository = Mockery::mock(GroupSnapshotRepositoryInterface::class);
        $groupSnapshotRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->app->instance(GroupSnapshotFactoryInterface::class, $groupSnapshotFactory);
        $this->app->instance(GroupSnapshotRepositoryInterface::class, $groupSnapshotRepository);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishedGroup = $publishGroup->process($input);
        $this->assertSame((string)$dummyPublishGroup->publishedGroupIdentifier, (string)$publishedGroup->groupIdentifier());
        $this->assertSame($dummyPublishGroup->language->value, $publishedGroup->language()->value);
        $this->assertSame((string)$dummyPublishGroup->translationSetIdentifier, (string)$publishedGroup->translationSetIdentifier());
        $this->assertSame((string)$dummyPublishGroup->agencyIdentifier, (string)$publishedGroup->agencyIdentifier());
        $this->assertSame((string)$dummyPublishGroup->agencyIdentifier, (string)$publishedGroup->agencyIdentifier());
        $this->assertSame((string)$dummyPublishGroup->description, (string)$publishedGroup->description());
        $this->assertSame($dummyPublishGroup->songIdentifiers, $publishedGroup->songIdentifiers());
        $this->assertSame((string)$dummyPublishGroup->imagePath, (string)$publishedGroup->imagePath());
        $this->assertSame($dummyPublishGroup->version->value(), $publishedGroup->version()->value());
    }

    /**
     * 異常系：指定したIDに紐づくGroupが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn(null);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(GroupNotFoundException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(true);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);
        $this->expectException(ExistsApprovedButNotTranslatedGroupException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：公開されている事務所情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup(hasPublishedGroup: true);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            $dummyPublishGroup->publishedGroupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishGroup->publishedGroupIdentifier)
            ->andReturn(null);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(GroupNotFoundException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがGroupを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $result = $publishGroup->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $dummyPublishGroup->status);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所のグループを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所に所属するグループを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, null, [$anotherGroupId], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedGroupActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            groupId: $groupId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedMemberScope(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, null, [$anotherGroupId], [$memberId]);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedMemberActor(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, null, [$groupId], [$memberId]);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            groupId: $groupId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummyPublishGroup = $this->createDummyPublishGroup(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->createdGroup)
            ->andReturn(null);
        $groupRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishGroup->draftGroup)
            ->andReturn(null);

        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->language, $dummyPublishGroup->name)
            ->andReturn($dummyPublishGroup->createdGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupService->shouldReceive('existsApprovedButNotTranslatedGroup')
            ->once()
            ->with($dummyPublishGroup->translationSetIdentifier, $dummyPublishGroup->groupIdentifier)
            ->andReturn(false);

        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);
        $groupHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishGroup->history);

        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishGroup->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * 異常系：NONEロールがグループを公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyPublishGroup = $this->createDummyPublishGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new PublishGroupInput(
            $dummyPublishGroup->groupIdentifier,
            null,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishGroup->groupIdentifier)
            ->andReturn($dummyPublishGroup->draftGroup);

        $groupService = Mockery::mock(GroupServiceInterface::class);
        $groupHistoryRepository = Mockery::mock(GroupHistoryRepositoryInterface::class);
        $groupHistoryFactory = Mockery::mock(GroupHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(GroupServiceInterface::class, $groupService);
        $this->app->instance(GroupHistoryRepositoryInterface::class, $groupHistoryRepository);
        $this->app->instance(GroupHistoryFactoryInterface::class, $groupHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishGroup = $this->app->make(PublishGroupInterface::class);
        $publishGroup->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $groupId
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @param bool $hasPublishedGroup
     * @param EditorIdentifier|null $operatorIdentifier
     * @return PublishGroupTestData
     */
    private function createDummyPublishGroup(
        ?string $groupId = null,
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        bool $hasPublishedGroup = false,
        ?EditorIdentifier $operatorIdentifier = null,
    ): PublishGroupTestData {
        $groupIdentifier = new GroupIdentifier($groupId ?? StrTestHelper::generateUlid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/after.webp');

        $draftGroup = new DraftGroup(
            $groupIdentifier,
            $hasPublishedGroup ? $publishedGroupIdentifier : null,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $status,
        );

        // 公開済みのGroupエンティティ（既存データを想定）
        $exName = new GroupName('aespa');
        $exAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $exDescription = new Description('## aespa: 가상과 현실을 넘나드는 K팝 그룹
에스파(aespa)는 2020년 11월 17일 SM엔터테인먼트에서 데뷔한 4인조 다국적 걸그룹입니다. 그룹명 \'aespa\'는 \'Avatar X Experience\'를 표현한 \'æ\'와 양면이라는 뜻의 \'aspect\'를 결합하여 만든 이름으로, \'자신의 또 다른 자아인 아바타를 만나 새로운 세계를 경험하게 된다\'는 독특한 세계관을 가지고 있습니다.
**멤버 구성:**
에스파는 한국인 멤버 카리나(KARINA)와 윈터(WINTER), 일본인 멤버 지젤(GISELLE), 그리고 중국인 멤버 닝닝(NINGNING)으로 구성되어 있습니다. 각 멤버는 현실 세계의 멤버와 가상 세계의 아바타인 \'ae\'가 함께 존재하며 활동한다는 혁신적인 콘셉트를 선보이고 있습니다.
**주요 활동 및 음악 스타일:**
데뷔곡 \'Black Mamba\'를 시작으로 \'Next Level\', \'Savage\', \'Drama\' 등 발표하는 곡마다 강렬하고 미래지향적인 사운드와 함께 독자적인 세계관을 담은 가사로 큰 사랑을 받고 있습니다. 특히, 가상 세계 \'광야(KWANGYA)\'에서 조력자 \'nævis\'와 함께 악의 존재인 \'Black Mamba\'와 맞서 싸우는 스토리는 에스파의 핵심 서사입니다.
이처럼 에스파는 단순한 아이돌 그룹을 넘어, 메타버스라는 새로운 영역을 K팝에 접목시키며 전 세계 팬들에게 신선한 충격을 안겨주고 있는 그룹입니다.');
        $exSongIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $exImagePath = new ImagePath('/resources/public/images/after.webp');
        $publishedVersion = new Version(1);
        $publishedGroup = new Group(
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $language,
            $exName,
            'aespa',
            $exAgencyIdentifier,
            $exDescription,
            $exSongIdentifiers,
            $exImagePath,
            $publishedVersion,
        );

        // 新規作成用のGroup
        $version = new Version(1);
        $createdGroup = new Group(
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            null,
            new Description(''),
            [],
            null,
            $version,
        );

        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new GroupHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $draftGroup->editorIdentifier(),
            $hasPublishedGroup ? $publishedGroupIdentifier : null,
            $draftGroup->groupIdentifier(),
            $draftGroup->status(),
            null,
            $draftGroup->name(),
            new \DateTimeImmutable(),
        );

        // 公開済みGroupのスナップショット（更新時用）
        $snapshot = new GroupSnapshot(
            new GroupSnapshotIdentifier(StrTestHelper::generateUlid()),
            $publishedGroup->groupIdentifier(),
            $publishedGroup->translationSetIdentifier(),
            $publishedGroup->language(),
            $publishedGroup->name(),
            $publishedGroup->normalizedName(),
            $publishedGroup->agencyIdentifier(),
            $publishedGroup->description(),
            $publishedGroup->songIdentifiers(),
            $publishedGroup->imagePath(),
            $publishedGroup->version(),
            new \DateTimeImmutable('2024-01-01 00:00:00'),
        );

        return new PublishGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $status,
            $translationSetIdentifier,
            $draftGroup,
            $publishedGroup,
            $createdGroup,
            $version,
            $publishedVersion,
            $historyIdentifier,
            $history,
            $snapshot,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class PublishGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param SongIdentifier[] $songIdentifiers
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public GroupIdentifier          $publishedGroupIdentifier,
        public EditorIdentifier         $editorIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public array                    $songIdentifiers,
        public ImagePath                $imagePath,
        public ApprovalStatus           $status,
        public TranslationSetIdentifier $translationSetIdentifier,
        public DraftGroup               $draftGroup,
        public Group                    $publishedGroup,
        public Group                    $createdGroup,
        public Version                  $version,
        public Version                  $publishedVersion,
        public GroupHistoryIdentifier   $historyIdentifier,
        public GroupHistory             $history,
        public GroupSnapshot            $snapshot,
    ) {
    }
}
