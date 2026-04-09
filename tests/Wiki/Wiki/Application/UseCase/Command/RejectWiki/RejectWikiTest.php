<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\RejectWiki;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
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

class RejectWikiTest extends TestCase
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
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $this->assertInstanceOf(RejectWiki::class, $rejectWiki);
    }

    /**
     * 正常系：正しく下書きが却下されること.
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

        $dummyRejectWiki = $this->createDummyRejectWiki(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new RejectWikiInput(
            $dummyRejectWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyRejectWiki->resourceType,
            $dummyRejectWiki->agencyIdentifier,
            $dummyRejectWiki->groupIdentifiers,
            $dummyRejectWiki->talentIdentifiers,
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
            ->with($dummyRejectWiki->draftWiki)
            ->andReturn(null);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectWiki->wikiIdentifier)
            ->andReturn($dummyRejectWiki->draftWiki);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyRejectWiki->history);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyRejectWiki->history)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $output = new RejectWikiOutput();
        $rejectWiki->process($input, $output);
        $result = $output->toArray();
        $this->assertSame(ApprovalStatus::Rejected->value, $result['status']);
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
        $dummyRejectWiki = $this->createDummyRejectWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectWikiInput(
            $dummyRejectWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyRejectWiki->resourceType,
            $dummyRejectWiki->agencyIdentifier,
            $dummyRejectWiki->groupIdentifiers,
            $dummyRejectWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectWiki->wikiIdentifier)
            ->andReturn(null);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(WikiNotFoundException::class);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $rejectWiki->process($input, new RejectWikiOutput());
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
        $dummyRejectWiki = $this->createDummyRejectWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectWikiInput(
            $dummyRejectWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyRejectWiki->resourceType,
            $dummyRejectWiki->agencyIdentifier,
            $dummyRejectWiki->groupIdentifiers,
            $dummyRejectWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectWiki->wikiIdentifier)
            ->andReturn($dummyRejectWiki->draftWiki);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $rejectWiki->process($input, new RejectWikiOutput());
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
        $dummyRejectWiki = $this->createDummyRejectWiki(status: ApprovalStatus::Pending);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectWikiInput(
            $dummyRejectWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyRejectWiki->resourceType,
            $dummyRejectWiki->agencyIdentifier,
            $dummyRejectWiki->groupIdentifiers,
            $dummyRejectWiki->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($dummyRejectWiki->wikiIdentifier)
            ->andReturn($dummyRejectWiki->draftWiki);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $rejectWiki->process($input, new RejectWikiOutput());
    }

    /**
     * 異常系：権限を持たないユーザーがWikiを却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorized(): void
    {
        $dummyRejectWiki = $this->createDummyRejectWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new RejectWikiInput(
            $dummyRejectWiki->wikiIdentifier,
            $principalIdentifier,
            $dummyRejectWiki->resourceType,
            $dummyRejectWiki->agencyIdentifier,
            $dummyRejectWiki->groupIdentifiers,
            $dummyRejectWiki->talentIdentifiers,
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
            ->with($dummyRejectWiki->wikiIdentifier)
            ->andReturn($dummyRejectWiki->draftWiki);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(DisallowedException::class);
        $rejectWiki = $this->app->make(RejectWikiInterface::class);
        $rejectWiki->process($input, new RejectWikiOutput());
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return RejectWikiTestData
     */
    private function createDummyRejectWiki(
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): RejectWikiTestData {
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
            ApprovalStatus::Rejected,
            null,
            null,
            $draftWiki->basic()->name(),
            new DateTimeImmutable('now'),
        );

        return new RejectWikiTestData(
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
readonly class RejectWikiTestData
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
