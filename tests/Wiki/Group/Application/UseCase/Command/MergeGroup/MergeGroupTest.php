<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\MergeGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\MergeGroup\MergeGroup;
use Source\Wiki\Group\Application\UseCase\Command\MergeGroup\MergeGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\MergeGroup\MergeGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $this->assertInstanceOf(MergeGroup::class, $mergeGroup);
    }

    /**
     * 正常系：正しくGroup Entityがマージされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('save')
            ->once()
            ->with($dummyGroup->group)
            ->andReturn(null);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $group = $mergeGroup->process($input);
        $this->assertSame((string)$dummyGroup->groupIdentifier, (string)$group->groupIdentifier());
        $this->assertSame((string)$dummyGroup->publishedGroupIdentifier, (string)$group->publishedGroupIdentifier());
        $this->assertSame($dummyGroup->language->value, $group->language()->value);
        $this->assertSame((string)$dummyGroup->name, (string)$group->name());
        $this->assertSame((string)$dummyGroup->agencyIdentifier, (string)$group->agencyIdentifier());
        $this->assertSame((string)$dummyGroup->description, (string)$group->description());
        $this->assertSame($principalIdentifier, $group->mergerIdentifier());
        $this->assertSame($mergedAt, $group->mergedAt());
    }

    /**
     * 異常系：指定したIDに紐づくGroupが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundGroup(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->expectException(GroupNotFoundException::class);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがGroupをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $agencyId = (string) $dummyGroup->agencyIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);
        $draftGroupRepository->shouldReceive('save')
            ->once()
            ->with($dummyGroup->group)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがGroupをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $talentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [(string) $dummyGroup->groupIdentifier], [$talentId]);

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);
        $draftGroupRepository->shouldReceive('save')
            ->once()
            ->with($dummyGroup->group)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * 異常系：COLLABORATORがGroupをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * 異常系：NONEロールがGroupをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyGroup = $this->createDummyMergeGroup();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeGroupInput(
            $dummyGroup->groupIdentifier,
            $dummyGroup->name,
            $dummyGroup->agencyIdentifier,
            $dummyGroup->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftGroupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $draftGroupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyGroup->groupIdentifier)
            ->andReturn($dummyGroup->group);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftGroupRepositoryInterface::class, $draftGroupRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $mergeGroup = $this->app->make(MergeGroupInterface::class);
        $mergeGroup->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return MergeGroupTestData
     */
    private function createDummyMergeGroup(): MergeGroupTestData
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다.');
        $imagePath = new ImagePath('/resources/public/images/twice.webp');
        $status = ApprovalStatus::Pending;

        $group = new DraftGroup(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $imagePath,
            $status,
        );

        return new MergeGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $imagePath,
            $status,
            $group,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class MergeGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public GroupIdentifier          $publishedGroupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public ImagePath                $imagePath,
        public ApprovalStatus           $status,
        public DraftGroup               $group,
    ) {
    }
}
