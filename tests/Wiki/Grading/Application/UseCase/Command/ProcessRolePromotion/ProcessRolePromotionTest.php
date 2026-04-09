<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInput;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionInterface;
use Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionOutput;
use Source\Wiki\Grading\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\Event\DemotionWarningsBatchIssued;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\WarningCount;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProcessRolePromotionTest extends TestCase
{
    /**
     * 正常系: 上位10%のCollaboratorが昇格されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessPromotesTop10Percent(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $collaboratorRoleId = StrTestHelper::generateUuid();
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $collaboratorRole = new Role(
            new RoleIdentifier($collaboratorRoleId),
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));
        $principalGroup->addRole(new RoleIdentifier($collaboratorRoleId));

        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(100),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([]);

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);
        $promotionHistoryRepository->shouldReceive('save')
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $collaboratorRoleId))
            ->andReturn([$principalGroup]);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([]);
        $principalGroupRepository->shouldReceive('findByPrincipalId')
            ->andReturn([$principalGroup]);
        $principalGroupRepository->shouldReceive('save')
            ->once();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn($collaboratorRole);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->andReturn(StrTestHelper::generateUuid());

        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityId,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')
            ->andReturn([$principal]);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once();

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        $this->assertCount(1, $output->promoted());
        $this->assertSame($principalId, (string) $output->promoted()[0]);
    }

    /**
     * 正常系: 50pt未満のユーザーは昇格対象外となること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessExcludesBelowThreshold(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $principalId = StrTestHelper::generateUuid();

        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(49),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([]);
        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')->andReturn(null);
        $principalGroupRepository->shouldReceive('findByRole')->andReturn([]);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        $this->assertCount(0, $output->promoted());
    }

    /**
     * 正常系: 上位10%に入ったSenior Collaboratorの警告がリセットされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessResetsWarningWhenInTop10Percent(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $collaboratorRoleId = StrTestHelper::generateUuid();
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $warningId = StrTestHelper::generateUuid();

        $collaboratorRole = new Role(
            new RoleIdentifier($collaboratorRoleId),
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));
        $principalGroup->addRole(new RoleIdentifier($seniorCollaboratorRoleId));

        // 既存の警告（warningCount=2）
        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(2),
            new YearMonth('2025-12'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        // 上位10%に入る高いポイント
        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(100),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([$warning]);
        // 警告がリセットされて保存されることを確認
        $demotionWarningRepository->shouldReceive('save')
            ->with(Mockery::on(static fn ($w) => (string) $w->principalIdentifier() === $principalId
                && $w->warningCount()->value() === 0))
            ->once();

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $collaboratorRoleId))
            ->andReturn([]);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([$principalGroup]);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn($collaboratorRole);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        // 降格されていないことを確認
        $this->assertCount(0, $output->demoted());
        // 警告がリセットされていることを確認
        $this->assertSame(0, $warning->warningCount()->value());
    }

    /**
     * 正常系: 上位10%から外れたSenior Collaboratorに初回警告が作成されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCreatesFirstWarningWhenOutsideTop10Percent(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $collaboratorRoleId = StrTestHelper::generateUuid();
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $collaboratorRole = new Role(
            new RoleIdentifier($collaboratorRoleId),
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));
        $principalGroup->addRole(new RoleIdentifier($seniorCollaboratorRoleId));

        // 上位10%に入らない低いポイント
        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(30),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        // 既存の警告がない
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([]);
        // 新規警告が作成されて保存されることを確認
        $demotionWarningRepository->shouldReceive('save')
            ->with(Mockery::on(static fn ($w) => (string) $w->principalIdentifier() === $principalId
                && $w->warningCount()->value() === 1))
            ->once();

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $collaboratorRoleId))
            ->andReturn([]);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([$principalGroup]);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn($collaboratorRole);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->andReturn(StrTestHelper::generateUuid());
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        // 降格されていないことを確認（初回警告なので降格閾値に達していない）
        $this->assertCount(0, $output->demoted());
        // 警告対象になっていないことを確認（warningCount=1はisReachedWarningThresholdがfalse）
        $this->assertCount(0, $output->warned());
    }

    /**
     * 正常系: COLLABORATORロールが存在しない場合、昇格・降格処理がスキップされること
     *
     * processDemotionsとprocessPromotionsの両方でCOLLABORATORロールのnullチェックによりearly returnされる
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessSkipsPromotionAndDemotionWhenCollaboratorRoleNotFound(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        // 上位10%に入る高いポイント
        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(100),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([]);

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        // processWarningsでgetSeniorCollaboratorsが呼ばれるため、findByRoleが必要
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([]);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        // COLLABORATORロールが見つからない → processDemotionsとprocessPromotionsでearly return
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn(null);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        // COLLABORATORロールが見つからないため昇格・降格処理がスキップされる
        $this->assertCount(0, $output->promoted());
        $this->assertCount(0, $output->demoted());
    }

    /**
     * 正常系: 3回連続で上位10%から外れたSenior Collaboratorが降格されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessDemotesAfterThreeWarnings(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $collaboratorRoleId = StrTestHelper::generateUuid();
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $warningId = StrTestHelper::generateUuid();

        $collaboratorRole = new Role(
            new RoleIdentifier($collaboratorRoleId),
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));
        $principalGroup->addRole(new RoleIdentifier($seniorCollaboratorRoleId));

        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(2),
            new YearMonth('2025-12'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(30),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([$warning]);
        $demotionWarningRepository->shouldReceive('save')
            ->once();
        $demotionWarningRepository->shouldReceive('delete')
            ->once();

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);
        $promotionHistoryRepository->shouldReceive('save')
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $collaboratorRoleId))
            ->andReturn([]);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([$principalGroup]);
        $principalGroupRepository->shouldReceive('findByPrincipalId')
            ->andReturn([$principalGroup]);
        $principalGroupRepository->shouldReceive('save')
            ->once();

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn($collaboratorRole);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->andReturn(StrTestHelper::generateUuid());

        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityId,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')
            ->andReturn([$principal]);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once();

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        $this->assertCount(1, $output->demoted());
        $this->assertSame($principalId, (string) $output->demoted()[0]);
    }

    /**
     * 正常系: 2回目の警告を受けたSenior Collaboratorに対してDemotionWarningsBatchIssuedイベントが発行されること
     *
     * processDemotionsでwarningCountが1から2にincrementされ、
     * processWarningsでisReachedWarningThreshold（warningCount === 2）がtrueとなり、
     * DemotionWarningsBatchIssuedイベントがディスパッチされる。
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessDispatchesDemotionWarningsBatchIssuedEvent(): void
    {
        $yearMonth = new YearMonth('2026-01');
        $collaboratorRoleId = StrTestHelper::generateUuid();
        $seniorCollaboratorRoleId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $warningId = StrTestHelper::generateUuid();

        $collaboratorRole = new Role(
            new RoleIdentifier($collaboratorRoleId),
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $seniorCollaboratorRole = new Role(
            new RoleIdentifier($seniorCollaboratorRoleId),
            'SENIOR_COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($groupId),
            new AccountIdentifier($accountId),
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));
        $principalGroup->addRole(new RoleIdentifier($seniorCollaboratorRoleId));

        // WarningCount=1の既存警告（processDemotionsでincrementされて2になり、isReachedWarningThreshold=trueになる）
        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(1),
            new YearMonth('2025-12'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        // 上位10%に入らない低いポイント
        $summary = new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            $yearMonth,
            new Point(30),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $summaryRepository = Mockery::mock(ContributionPointSummaryRepositoryInterface::class);
        $summaryRepository->shouldReceive('findByYearMonths')
            ->andReturn([$summary]);

        $demotionWarningRepository = Mockery::mock(DemotionWarningRepositoryInterface::class);
        $demotionWarningRepository->shouldReceive('findAll')
            ->andReturn([$warning]);
        $demotionWarningRepository->shouldReceive('save')
            ->once();

        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $collaboratorRoleId))
            ->andReturn([]);
        $principalGroupRepository->shouldReceive('findByRole')
            ->with(Mockery::on(static fn ($r) => (string) $r === $seniorCollaboratorRoleId))
            ->andReturn([$principalGroup]);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->andReturn($collaboratorRole);
        $roleRepository->shouldReceive('findByName')
            ->with('SENIOR_COLLABORATOR')
            ->andReturn($seniorCollaboratorRole);

        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')
            ->andReturn(StrTestHelper::generateUuid());

        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityId,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')
            ->andReturn([$principal]);

        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        // DemotionWarningsBatchIssuedが発行されることを確認
        $dispatcher->shouldReceive('dispatch')
            ->with(Mockery::on(static fn ($event) => $event instanceof DemotionWarningsBatchIssued))
            ->once();

        $this->app->instance(ContributionPointSummaryRepositoryInterface::class, $summaryRepository);
        $this->app->instance(DemotionWarningRepositoryInterface::class, $demotionWarningRepository);
        $this->app->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $this->app->instance(EventDispatcherInterface::class, $dispatcher);

        $useCase = $this->app->make(ProcessRolePromotionInterface::class);
        $input = new ProcessRolePromotionInput($yearMonth);
        $output = new ProcessRolePromotionOutput();

        $useCase->process($input, $output);

        // warnedにプリンシパルが含まれていることを確認
        $this->assertCount(1, $output->warned());
        $this->assertSame($principalId, (string) $output->warned()[0]);
        // 降格されていないことを確認（warningCount=2なので降格閾値の3に達していない）
        $this->assertCount(0, $output->demoted());
    }
}
