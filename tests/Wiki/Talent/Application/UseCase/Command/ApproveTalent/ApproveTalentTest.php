<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\ApproveTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent\ApproveTalent;
use Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent\ApproveTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent\ApproveTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveTalentTest extends TestCase
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
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $talentService = Mockery::mock(TalentServiceInterface::class);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $this->assertInstanceOf(ApproveTalent::class, $approveTalent);
    }

    /**
     * 正常系：正しく下書きが承認されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $talent = $approveTalent->process($input);
        $this->assertNotSame($approveTalentInfo->status, $talent->status());
        $this->assertSame(ApprovalStatus::Approved, $talent->status());
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがメンバーを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $result = $approveTalent->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：指定したIDに紐づくTalentが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundTalent(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(TalentNotFoundException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $status = ApprovalStatus::Approved;
        $talent = new DraftTalent(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $approveTalentInfo->translationSetIdentifier,
            $approveTalentInfo->editorIdentifier,
            $approveTalentInfo->translation,
            $approveTalentInfo->name,
            $approveTalentInfo->realName,
            $approveTalentInfo->agencyIdentifier,
            $approveTalentInfo->groupIdentifiers,
            $approveTalentInfo->birthday,
            $approveTalentInfo->career,
            $approveTalentInfo->imageLink,
            $approveTalentInfo->relevantVideoLinks,
            $status,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($talent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(true);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(ExistsApprovedButNotTranslatedTalentException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループのメンバーを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループのタレントを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $agencyId = (string) $approveTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $approveTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, $groupIds, []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $result = $approveTalent->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループのメンバーを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $approveTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループのタレントを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedGroupActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $agencyId = (string) $approveTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $approveTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::GROUP_ACTOR, $agencyId, $groupIds, []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $result = $approveTalent->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループのTALENTを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string) $approveTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUuid();
        $anotherTalentId = StrTestHelper::generateUuid(); // 別のTalent IDを使用
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], [$anotherTalentId]);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループのメンバーを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $agencyId = (string) $approveTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $approveTalentInfo->groupIdentifiers);
        $talentId = (string) $approveTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, $groupIds, [$talentId]);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $result = $approveTalent->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $approveTalentInfo = $this->createApproveTalentInfo(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($approveTalentInfo->draftTalent)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentService->shouldReceive('existsApprovedButNotTranslatedTalent')
            ->once()
            ->with($approveTalentInfo->translationSetIdentifier, $approveTalentInfo->talentIdentifier)
            ->andReturn(false);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($approveTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($approveTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $result = $approveTalent->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：NONEロールがメンバーを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $approveTalentInfo = $this->createApproveTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new ApproveTalentInput(
            $approveTalentInfo->talentIdentifier,
            $approveTalentInfo->publishedTalentIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($approveTalentInfo->talentIdentifier)
            ->andReturn($approveTalentInfo->draftTalent);

        $talentService = Mockery::mock(TalentServiceInterface::class);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveTalent = $this->app->make(ApproveTalentInterface::class);
        $approveTalent->process($input);
    }

    /**
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return ApproveTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createApproveTalentInfo(
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): ApproveTalentTestData {
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translation = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $imageLink = new ImagePath('/resources/public/images/before.webp');

        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $version = new Version(1);
        $publishedTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $version,
        );

        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new TalentHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $talent->editorIdentifier(),
            $talent->publishedTalentIdentifier(),
            $talent->talentIdentifier(),
            ApprovalStatus::UnderReview,
            ApprovalStatus::Approved,
            $talent->name(),
            new DateTimeImmutable('now'),
        );

        return new ApproveTalentTestData(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $base64EncodedImage,
            $link1,
            $link2,
            $link3,
            $relevantVideoLinks,
            $imageLink,
            $talentIdentifier,
            $status,
            $talent,
            $publishedTalent,
            $version,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class ApproveTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier         $publishedTalentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $translation,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public string                   $base64EncodedImage,
        public ExternalContentLink      $link1,
        public ExternalContentLink      $link2,
        public ExternalContentLink      $link3,
        public RelevantVideoLinks       $relevantVideoLinks,
        public ImagePath                $imageLink,
        public TalentIdentifier         $talentIdentifier,
        public ApprovalStatus           $status,
        public DraftTalent              $draftTalent,
        public Talent                   $publishedTalent,
        public Version                  $version,
        public TalentHistoryIdentifier  $historyIdentifier,
        public TalentHistory            $history,
    ) {
    }
}
