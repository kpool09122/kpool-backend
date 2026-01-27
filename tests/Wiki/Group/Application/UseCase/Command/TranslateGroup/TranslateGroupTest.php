<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Mockery\MockInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslatedGroupData;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroup;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\TranslateGroup\TranslateGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateGroupTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $this->assertInstanceOf(TranslateGroup::class, $translateGroup);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateGroup->jaTranslatedData);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateGroup->enTranslatedData);

        $draftGroupFactory = $this->createDraftGroupFactoryMock($dummyTranslateGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->app->instance(DraftGroupFactoryInterface::class, $draftGroupFactory);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系： 指定したIDのグループ情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     */
    public function testWhenAgencyNotFound(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateGroupInput(
            $groupIdentifier,
            $principalIdentifier,
        );

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($groupIdentifier)
            ->once()
            ->andReturn(null);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->expectException(GroupNotFoundException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateGroup->groupIdentifier)
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateGroup->jaTranslatedData);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateGroup->enTranslatedData);

        $draftGroupFactory = $this->createDraftGroupFactoryMock($dummyTranslateGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->app->instance(DraftGroupFactoryInterface::class, $draftGroupFactory);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所のグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $anotherAgencyId, [], []);

        $input = new TranslateGroupInput($dummyTranslateGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateGroup->groupIdentifier)
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所に所属するグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();
        $agencyId = (string) $dummyTranslateGroup->agencyIdentifier;

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $input = new TranslateGroupInput($dummyTranslateGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateGroup->jaTranslatedData);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateGroup->enTranslatedData);

        $draftGroupFactory = $this->createDraftGroupFactoryMock($dummyTranslateGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->app->instance(DraftGroupFactoryInterface::class, $draftGroupFactory);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedMemberScope(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherGroupId = StrTestHelper::generateUuid();
        $memberId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [$anotherGroupId], [$memberId]);

        $input = new TranslateGroupInput($dummyTranslateGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTranslateGroup->groupIdentifier)
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedMemberActor(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $memberId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [(string) $dummyTranslateGroup->groupIdentifier], [$memberId]);

        $input = new TranslateGroupInput($dummyTranslateGroup->groupIdentifier, $principalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateGroup->jaTranslatedData);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateGroup->enTranslatedData);

        $draftGroupFactory = $this->createDraftGroupFactoryMock($dummyTranslateGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->app->instance(DraftGroupFactoryInterface::class, $draftGroupFactory);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがグループを翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateGroup->jaTranslatedData);
        $translationService->shouldReceive('translateGroup')
            ->with($dummyTranslateGroup->group, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateGroup->enTranslatedData);

        $draftGroupFactory = $this->createDraftGroupFactoryMock($dummyTranslateGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->app->instance(DraftGroupFactoryInterface::class, $draftGroupFactory);

        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $groups = $translateGroup->process($input);

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(DraftGroup::class, $groups[0]);
        $this->assertInstanceOf(DraftGroup::class, $groups[1]);
    }

    /**
     * 異常系：NONEロールがグループを翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyTranslateGroup = $this->createDummyTranslateGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new TranslateGroupInput(
            $dummyTranslateGroup->groupIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->with($dummyTranslateGroup->groupIdentifier)
            ->once()
            ->andReturn($dummyTranslateGroup->group);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldNotReceive('save');
        $draftGroupRepository->shouldNotReceive('save');

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateGroup = $this->app->make(TranslateGroupInterface::class);
        $translateGroup->process($input);
    }

    /**
     * DraftGroupFactoryのモックを作成するヘルパーメソッド
     *
     * @param TranslateGroupTestData $dummyTranslateGroup
     * @return MockInterface&DraftGroupFactoryInterface
     */
    private function createDraftGroupFactoryMock(TranslateGroupTestData $dummyTranslateGroup): MockInterface
    {
        /** @var MockInterface&DraftGroupFactoryInterface $draftGroupFactory */
        $draftGroupFactory = Mockery::mock(DraftGroupFactoryInterface::class);
        $draftGroupFactory->shouldReceive('create')
            ->with(
                null,
                Language::JAPANESE,
                Mockery::type(GroupName::class),
                $dummyTranslateGroup->group->slug(),
                $dummyTranslateGroup->group->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($dummyTranslateGroup->jaGroup);
        $draftGroupFactory->shouldReceive('create')
            ->with(
                null,
                Language::ENGLISH,
                Mockery::type(GroupName::class),
                $dummyTranslateGroup->group->slug(),
                $dummyTranslateGroup->group->translationSetIdentifier(),
            )
            ->once()
            ->andReturn($dummyTranslateGroup->enGroup);

        return $draftGroupFactory;
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return TranslateGroupTestData
     */
    private function createDummyTranslateGroup(): TranslateGroupTestData
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description(<<<DESC
### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 'SIXTEEN'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다.
DESC);
        $version = new Version(1);

        $slug = new Slug('twice');
        $group = new Group(
            $groupIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            'twice',
            $agencyIdentifier,
            $description,
            $version,
            editorIdentifier: $editorIdentifier,
        );

        $jaGroup = new DraftGroup(
            new GroupIdentifier(StrTestHelper::generateUuid()),
            null,
            $translationSetIdentifier,
            new Slug('twice'),
            null,
            Language::JAPANESE,
            new GroupName('TWICE'),
            'トゥワイス',
            null,
            new Description(''),
            ApprovalStatus::Pending,
        );

        $enGroup = new DraftGroup(
            new GroupIdentifier(StrTestHelper::generateUuid()),
            null,
            $translationSetIdentifier,
            new Slug('twice'),
            null,
            Language::ENGLISH,
            new GroupName('TWICE'),
            'twice',
            null,
            new Description(''),
            ApprovalStatus::Pending,
        );

        $jaTranslatedData = new TranslatedGroupData(
            translatedName: 'TWICE',
            translatedDescription: '### TWICE：世界を魅了する9人組ガールズグループ',
        );

        $enTranslatedData = new TranslatedGroupData(
            translatedName: 'TWICE',
            translatedDescription: '### TWICE: The 9-Member Girl Group That Captivated the World',
        );

        return new TranslateGroupTestData(
            $groupIdentifier,
            $editorIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $description,
            $version,
            $group,
            $jaGroup,
            $enGroup,
            $jaTranslatedData,
            $enTranslatedData,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class TranslateGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public Version                  $version,
        public Group                    $group,
        public DraftGroup               $jaGroup,
        public DraftGroup               $enGroup,
        public TranslatedGroupData      $jaTranslatedData,
        public TranslatedGroupData      $enTranslatedData,
    ) {
    }
}
