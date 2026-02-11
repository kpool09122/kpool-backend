<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\ApproveWiki;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\ExistsApprovedDraftWikiException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $this->assertInstanceOf(ApproveWiki::class, $approveWiki);
    }

    /**
     * 正常系：正しく下書きが承認されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyApproveWiki = $this->createDummyApproveWiki(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveWiki->draftWiki)
            ->andReturn(null);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('existsBySlug')
            ->once()
            ->with($dummyApproveWiki->slug)
            ->andReturn(false);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('existsApprovedDraftWiki')
            ->once()
            ->with($dummyApproveWiki->translationSetIdentifier, $dummyApproveWiki->wikiIdentifier)
            ->andReturn(false);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyApproveWiki->history);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyApproveWiki->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $output = new ApproveWikiOutput();
        $approveWiki->process($input, $output);
        $result = $output->toArray();
        $this->assertSame(ApprovalStatus::Approved->value, $result['status']);
    }

    /**
     * 異常系：指定したIDに紐づくWikiが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundWiki(): void
    {
        $dummyApproveWiki = $this->createDummyApproveWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(WikiNotFoundException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws WikiNotFoundException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyApproveWiki = $this->createDummyApproveWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummyApproveWiki = $this->createDummyApproveWiki(status: ApprovalStatus::Pending);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * 異常系：権限を持たないユーザーがWikiを承認しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorized(): void
    {
        $dummyApproveWiki = $this->createDummyApproveWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(DisallowedException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * 異常系：指定したSlugが既に存在する場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testDuplicateSlug(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyApproveWiki = $this->createDummyApproveWiki(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldNotReceive('save');
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('existsBySlug')
            ->once()
            ->with($dummyApproveWiki->slug)
            ->andReturn(true);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(DuplicateSlugException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * 異常系：同じ翻訳セットに承認済みのDraftWikiが存在する場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testExistsApprovedDraftWiki(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $dummyApproveWiki = $this->createDummyApproveWiki(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new ApproveWikiInput(
            $dummyApproveWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyApproveWiki->resourceType,
            $dummyApproveWiki->agencyIdentifier,
            $dummyApproveWiki->groupIdentifiers,
            $dummyApproveWiki->talentIdentifiers,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyApproveWiki->wikiIdentifier)
            ->andReturn($dummyApproveWiki->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('existsBySlug')
            ->once()
            ->with($dummyApproveWiki->slug)
            ->andReturn(false);

        $wikiService = Mockery::mock(WikiServiceInterface::class);
        $wikiService->shouldReceive('existsApprovedDraftWiki')
            ->once()
            ->with($dummyApproveWiki->translationSetIdentifier, $dummyApproveWiki->wikiIdentifier)
            ->andReturn(true);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(WikiServiceInterface::class, $wikiService);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(ExistsApprovedDraftWikiException::class);
        $approveWiki = $this->app->make(ApproveWikiInterface::class);
        $approveWiki->process($input, new ApproveWikiOutput());
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return ApproveWikiTestData
     */
    private function createDummyApproveWiki(
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): ApproveWikiTestData {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $slug = new Slug('twice');
        $name = new Name('TWICE');
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [];
        $talentIdentifiers = [];

        $basic = new GroupBasic(
            name: $name,
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $sections = new SectionContentCollection();
        $themeColor = new Color('#FF5733');

        $draftWiki = new DraftWiki(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $status,
            $editorIdentifier,
        );

        $historyIdentifier = new WikiHistoryIdentifier(StrTestHelper::generateUuid());
        $history = new WikiHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $operatorIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $draftWiki->editorIdentifier(),
            $draftWiki->publishedWikiIdentifier(),
            new DraftWikiIdentifier((string) $draftWiki->wikiIdentifier()),
            $status,
            ApprovalStatus::Approved,
            null,
            null,
            $draftWiki->basic()->name(),
            new DateTimeImmutable('now'),
        );

        return new ApproveWikiTestData(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $resourceType,
            $slug,
            $name,
            $basic,
            $sections,
            $themeColor,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
            $status,
            $draftWiki,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class ApproveWikiTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        public DraftWikiIdentifier      $wikiIdentifier,
        public WikiIdentifier           $publishedWikiIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public ResourceType             $resourceType,
        public Slug                     $slug,
        public Name                     $name,
        public GroupBasic               $basic,
        public SectionContentCollection $sections,
        public ?Color                   $themeColor,
        public ?WikiIdentifier          $agencyIdentifier,
        public array                    $groupIdentifiers,
        public array                    $talentIdentifiers,
        public ApprovalStatus           $status,
        public DraftWiki                $draftWiki,
        public WikiHistoryIdentifier    $historyIdentifier,
        public WikiHistory              $history,
    ) {
    }
}
