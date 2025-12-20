<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgency;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency\ApproveAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $this->assertInstanceOf(ApproveAgency::class, $approveAgency);
    }

    /**
     * 正常系：正しくAgency Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $dummyApproveAgency = $this->createDummyApproveAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyApproveAgency->agency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyApproveAgency->translationSetIdentifier, $dummyApproveAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $agency = $approveAgency->process($input);
        $this->assertNotSame($dummyApproveAgency->status, $agency->status());
        $this->assertSame(ApprovalStatus::Approved, $agency->status());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(AgencyNotFoundException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testInvalidStatus(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency(null, ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(InvalidStatusException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyApproveAgency->translationSetIdentifier, $dummyApproveAgency->agencyIdentifier)
            ->andReturn(true);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $this->expectException(ExistsApprovedButNotTranslatedAgencyException::class);
        $approveAgency->process($input);
    }

    /**
     * 異常系：承認権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが事務所を承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $dummyApproveAgency = $this->createDummyApproveAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyApproveAgency->agency)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyApproveAgency->translationSetIdentifier, $dummyApproveAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $result = $approveAgency->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所を承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所を承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummyApproveAgency = $this->createDummyApproveAgency(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyApproveAgency->agency)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyApproveAgency->translationSetIdentifier, $dummyApproveAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $result = $approveAgency->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：GROUP_ACTORが事務所を承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupActor(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 異常系：TALENT_ACTORが事務所を承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedTalentActor(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが事務所を承認できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], []);

        $dummyApproveAgency = $this->createDummyApproveAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);
        $agencyRepository->shouldReceive('saveDraft')
            ->once()
            ->with($dummyApproveAgency->agency)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyApproveAgency->translationSetIdentifier, $dummyApproveAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveAgency->history);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $result = $approveAgency->process($input);

        $this->assertSame(ApprovalStatus::Approved, $result->status());
    }

    /**
     * 異常系：NONEロールが事務所を承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyApproveAgency = $this->createDummyApproveAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], []);

        $input = new ApproveAgencyInput(
            $dummyApproveAgency->agencyIdentifier,
            $dummyApproveAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyApproveAgency->agencyIdentifier)
            ->andReturn($dummyApproveAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $approveAgency = $this->app->make(ApproveAgencyInterface::class);
        $approveAgency->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @return ApproveAgencyTestData
     */
    private function createDummyApproveAgency(
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        ?EditorIdentifier $operatorIdentifier = null,
    ): ApproveAgencyTestData {
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'JYPㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터테インメント (JYP Entertainment)
가수 겸 음악 프로デュー서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인メント 기업입니다。HYBE, SM, YG엔터테인먼트と 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다。
**'진실, 성실, 겸손'**이라는 가치관を 매우 중시하며, 소속 아티ストの 노래やダンス 실력だけではなく 인성을 존중する 육성 방침으로 알려져 있습니다。 이러한 철학は 박진영이 オーディション 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다。
음악적인 면では 설립자인 박진영이 직접 プロデューサーとして 多くの曲 작업に 참여しており、대중에게 사랑받는 캐ッチ한 히트곡を 수많이 만들어왔습니다。
---
### 주요 소속 아ーティスト
지금까지 **원더걸ス(Wonder Girls)**, **2PM**, **ミ쓰에이(Miss A)**と 같이 K팝의 역사를 만들어 온 그룹들을 배출してきました。
현재도
* **트와이스 (TWICE)**
* **스트레이 キ즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 グループが 다수 所属되어 있으며、K팝의 グローバル한 발전에서 중심적인 역할을 계속해서 맡고 있습니다。음악 사업 외に 배우 マネジメントや 공연 事業도 하고 있습니다。
DESC);

        $agency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $status,
        );

        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new AgencyHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $agency->editorIdentifier(),
            null,
            $agency->agencyIdentifier(),
            ApprovalStatus::UnderReview,
            ApprovalStatus::Approved,
            $agency->name(),
            new DateTimeImmutable('now'),
        );

        return new ApproveAgencyTestData(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $status,
            $agency,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class ApproveAgencyTestData
{
    public function __construct(
        public AgencyIdentifier $agencyIdentifier,
        public AgencyIdentifier $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Language $language,
        public AgencyName $name,
        public CEO $CEO,
        public FoundedIn $foundedIn,
        public Description $description,
        public ApprovalStatus $status,
        public DraftAgency $agency,
        public AgencyHistoryIdentifier $historyIdentifier,
        public AgencyHistory $history,
    ) {
    }
}
