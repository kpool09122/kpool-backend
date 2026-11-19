<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiOutput;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiTarget;
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
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\Helper\VisitorLocationTestHelper;
use Tests\TestCase;

class SubmitWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $this->assertInstanceOf(SubmitWiki::class, $submitWiki);
    }

    /**
     * 正常系：正しく下書きステータスが変更されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    #[DataProviderExternal(VisitorLocationTestHelper::class, 'locations')]
    public function testProcess(VisitorLocation $visitorLocation): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $dummySubmitWiki = $this->createDummySubmitWiki(
            operatorIdentifier: $principalIdentifier,
        );

        $input = new SubmitWikiInput(
            [new SubmitWikiTarget(
                $dummySubmitWiki->wikiIdentifier,
                $dummySubmitWiki->resourceType,
                $dummySubmitWiki->agencyIdentifier,
                $dummySubmitWiki->groupIdentifiers,
                $dummySubmitWiki->talentIdentifiers,
            )],
            $principalIdentifier,
            visitorLocation: $visitorLocation,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitWiki->draftWiki)
            ->andReturn(null);
        $draftWikiRepository->shouldReceive('findByIds')
            ->once()
            ->with([$dummySubmitWiki->wikiIdentifier])
            ->andReturn([$dummySubmitWiki->draftWiki]);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryFactory->shouldReceive('create')
            ->withArgs(static fn (...$args) => $args[1] === $input->principalIdentifier()
                && $args[10] === $input->visitorLocation())
            ->once()
            ->andReturn($dummySubmitWiki->history);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummySubmitWiki->history)
            ->andReturn(null);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $output = new SubmitWikiOutput();
        $submitWiki->process($input, $output);
        $result = $output->toArray();
        $this->assertSame(ApprovalStatus::UnderReview->value, $result[0]['status']);
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
        $dummySubmitWiki = $this->createDummySubmitWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitWikiInput(
            [new SubmitWikiTarget(
                $dummySubmitWiki->wikiIdentifier,
                $dummySubmitWiki->resourceType,
                $dummySubmitWiki->agencyIdentifier,
                $dummySubmitWiki->groupIdentifiers,
                $dummySubmitWiki->talentIdentifiers,
            )],
            $principalIdentifier,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByIds')
            ->once()
            ->with([$dummySubmitWiki->wikiIdentifier])
            ->andReturn([]);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(WikiNotFoundException::class);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $submitWiki->process($input, new SubmitWikiOutput());
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
        $dummySubmitWiki = $this->createDummySubmitWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitWikiInput(
            [new SubmitWikiTarget(
                $dummySubmitWiki->wikiIdentifier,
                $dummySubmitWiki->resourceType,
                $dummySubmitWiki->agencyIdentifier,
                $dummySubmitWiki->groupIdentifiers,
                $dummySubmitWiki->talentIdentifiers,
            )],
            $principalIdentifier,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByIds')
            ->once()
            ->with([$dummySubmitWiki->wikiIdentifier])
            ->andReturn([$dummySubmitWiki->draftWiki]);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(PrincipalNotFoundException::class);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $submitWiki->process($input, new SubmitWikiOutput());
    }

    /**
     * 異常系：承認ステータスがPendingかRejected以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $dummySubmitWiki = $this->createDummySubmitWiki(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $input = new SubmitWikiInput(
            [new SubmitWikiTarget(
                $dummySubmitWiki->wikiIdentifier,
                $dummySubmitWiki->resourceType,
                $dummySubmitWiki->agencyIdentifier,
                $dummySubmitWiki->groupIdentifiers,
                $dummySubmitWiki->talentIdentifiers,
            )],
            $principalIdentifier,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByIds')
            ->once()
            ->with([$dummySubmitWiki->wikiIdentifier])
            ->andReturn([$dummySubmitWiki->draftWiki]);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $submitWiki->process($input, new SubmitWikiOutput());
    }

    /**
     * 異常系：権限を持たないユーザーがWikiを申請しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testDisallowed(): void
    {
        $dummySubmitWiki = $this->createDummySubmitWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));

        $input = new SubmitWikiInput(
            [new SubmitWikiTarget(
                $dummySubmitWiki->wikiIdentifier,
                $dummySubmitWiki->resourceType,
                $dummySubmitWiki->agencyIdentifier,
                $dummySubmitWiki->groupIdentifiers,
                $dummySubmitWiki->talentIdentifiers,
            )],
            $principalIdentifier,
            visitorLocation: new VisitorLocation('JP', '01'),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findByIds')
            ->once()
            ->with([$dummySubmitWiki->wikiIdentifier])
            ->andReturn([$dummySubmitWiki->draftWiki]);

        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $submitWiki->process($input, new SubmitWikiOutput());
    }

    #[DataProvider('batchCases')]
    public function testBatch(int $count, ?string $failure): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $data = [];
        $targets = [];
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $visitorLocation = new VisitorLocation('JP', '01');

        for ($index = 0; $index < $count; $index++) {
            $item = $this->createDummySubmitWiki(
                status: $index === $count - 1 && $failure === 'status' ? ApprovalStatus::UnderReview : ($index % 2 === 0 ? ApprovalStatus::Pending : ApprovalStatus::Rejected),
                operatorIdentifier: $principalIdentifier,
            );
            $data[] = $item;
            $targets[] = new SubmitWikiTarget($item->wikiIdentifier, $item->resourceType, $item->agencyIdentifier, $item->groupIdentifiers, $item->talentIdentifiers);
            if ($failure !== 'missing') {
                $policyEvaluator->shouldReceive('evaluate')->once()->withArgs(
                    static fn (Principal $actor, Action $action, Resource $resource): bool => $actor === $principal
                        && $action === Action::SUBMIT && $resource->agencyId() === (string) $item->agencyIdentifier,
                )->andReturn(! ($index === $count - 1 && $failure === 'permission'));
            }
            if ($failure === null) {
                $draftWikiRepository->shouldReceive('save')->once()->with($item->draftWiki);
                $wikiHistoryFactory->shouldReceive('create')->once()->withArgs(
                    static fn (...$args): bool => $args[4] instanceof DraftWikiIdentifier && (string) $args[4] === (string) $item->wikiIdentifier
                        && $args[5] === $item->status && $args[6] === ApprovalStatus::UnderReview
                        && $args[10] === $visitorLocation && $args[14] === $principal->identityIdentifier(),
                )->andReturn($item->history);
                $wikiHistoryRepository->shouldReceive('save')->once()->with($item->history);
            }
        }
        $found = $failure === 'missing' ? array_slice($data, 0, -1) : $data;
        $draftWikiRepository->shouldNotReceive('findById');
        $draftWikiRepository->shouldReceive('findByIds')->once()
            ->with(array_map(static fn (SubmitWikiTarget $target): DraftWikiIdentifier => $target->wikiIdentifier(), $targets))
            ->andReturn(array_reverse(array_map(static fn (SubmitWikiTestData $item): DraftWiki => $item->draftWiki, $found)));
        if ($failure === 'missing') {
            $principalRepository->shouldNotReceive('findById');
        } else {
            $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);
        }
        if ($failure !== null) {
            $draftWikiRepository->shouldNotReceive('save');
            $wikiHistoryRepository->shouldNotReceive('save');
            $wikiHistoryFactory->shouldNotReceive('create');
        }
        $this->app()->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
        $this->app()->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $submitWiki = $this->app()->make(SubmitWikiInterface::class);
        $output = new SubmitWikiOutput();

        try {
            $submitWiki->process(new SubmitWikiInput($targets, $principalIdentifier, $visitorLocation), $output);
            $this->assertNull($failure);
            $this->assertSame(array_map(static fn (SubmitWikiTestData $item): string => (string) $item->wikiIdentifier, $data), array_column($output->toArray(), 'wikiIdentifier'));
            foreach ($data as $item) {
                $this->assertSame(ApprovalStatus::UnderReview, $item->draftWiki->status());
            }
        } catch (WikiNotFoundException|DisallowedException|InvalidStatusException $exception) {
            $expected = match ($failure) {
                'missing' => WikiNotFoundException::class,
                'permission' => DisallowedException::class,
                'status' => InvalidStatusException::class,
                default => null,
            };
            $this->assertSame($expected, $exception::class);
            $this->assertSame([], $output->toArray());
            foreach ($data as $item) {
                $this->assertSame($item->status, $item->draftWiki->status());
            }
        }
    }

    /** @return array<string, array{int, ?string}> */
    public static function batchCases(): array
    {
        return [
            '100 drafts' => [100, null],
            'last draft missing' => [2, 'missing'],
            'last draft disallowed' => [2, 'permission'],
            'last draft already submitted' => [2, 'status'],
        ];
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $operatorIdentifier
     * @return SubmitWikiTestData
     */
    private function createDummySubmitWiki(
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?PrincipalIdentifier $operatorIdentifier = null,
    ): SubmitWikiTestData {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $slug = new Slug('gr-twice');
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
        );
        $sections = new SectionContentCollection();
        $themeColor = new HexColor('#ff5733');

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
            ApprovalStatus::UnderReview,
            null,
            null,
            $draftWiki->basic()->name(),
            new DateTimeImmutable('now'),
            new VisitorLocation(),
        );

        return new SubmitWikiTestData(
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
readonly class SubmitWikiTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        public DraftWikiIdentifier           $wikiIdentifier,
        public WikiIdentifier           $publishedWikiIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public ResourceType             $resourceType,
        public Slug                     $slug,
        public Name                     $name,
        public GroupBasic               $basic,
        public SectionContentCollection $sections,
        public ?HexColor                   $themeColor,
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
