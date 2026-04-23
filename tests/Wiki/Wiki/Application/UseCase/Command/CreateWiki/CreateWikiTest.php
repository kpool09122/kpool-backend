<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

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
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateWikiTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $createWiki = $this->app->make(CreateWikiInterface::class);
        $this->assertInstanceOf(CreateWiki::class, $createWiki);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     * @throws DuplicateSlugException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $testData = $this->createDummyCreateWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new CreateWikiInput(
            $testData->publishedWikiIdentifier,
            $testData->language,
            $testData->resourceType,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $testData->slug,
            $principalIdentifier,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase = $this->app->make(CreateWikiInterface::class);
        $useCase->process($input, new CreateWikiOutput());
    }

    /**
     * 異常系：権限がない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws DuplicateSlugException
     */
    public function testDisallowed(): void
    {
        $testData = $this->createDummyCreateWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new CreateWikiInput(
            $testData->publishedWikiIdentifier,
            $testData->language,
            $testData->resourceType,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $testData->slug,
            $principalIdentifier,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->andReturn(false);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(DisallowedException::class);
        $useCase = $this->app->make(CreateWikiInterface::class);
        $useCase->process($input, new CreateWikiOutput());
    }

    /**
     * 異常系：指定したSlugが既に存在する場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testDuplicateSlug(): void
    {
        $testData = $this->createDummyCreateWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new CreateWikiInput(
            $testData->publishedWikiIdentifier,
            $testData->language,
            $testData->resourceType,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $testData->slug,
            $principalIdentifier,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->andReturn(true);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('existsBySlug')
            ->once()
            ->with($testData->slug)
            ->andReturn(true);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(DuplicateSlugException::class);
        $useCase = $this->app->make(CreateWikiInterface::class);
        $useCase->process($input, new CreateWikiOutput());
    }

    /**
     * 正常系：正しくDraftWiki Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws DuplicateSlugException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyCreateWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new CreateWikiInput(
            $testData->publishedWikiIdentifier,
            $testData->language,
            $testData->resourceType,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $testData->slug,
            $principalIdentifier,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->andReturn(true);

        $wikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $wikiFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $testData->language, $testData->basic, $testData->slug)
            ->andReturn($testData->draftWiki);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('existsBySlug')
            ->once()
            ->with($testData->slug)
            ->andReturn(false);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->publishedWikiIdentifier)
            ->andReturn($testData->publishedWiki);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')
            ->once()
            ->with($testData->draftWiki)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(DraftWikiFactoryInterface::class, $wikiFactory);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $useCase = $this->app->make(CreateWikiInterface::class);
        $output = new CreateWikiOutput();
        $useCase->process($input, $output);
        $result = $output->toArray();

        $this->assertSame($testData->language->value, $result['language']);
        $this->assertSame((string) $testData->basic->name(), $result['name']);
        $this->assertSame($testData->resourceType->value, $result['resourceType']);
        $this->assertSame($testData->status->value, $result['status']);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return CreateWikiTestData
     */
    private function createDummyCreateWiki(): CreateWikiTestData
    {
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $resourceType = ResourceType::GROUP;
        $basic = new GroupBasic(
            name: new Name('TWICE'),
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
        $slug = new Slug('gr-twice');
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [];
        $talentIdentifiers = [];
        $status = ApprovalStatus::Pending;

        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

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

        $version = new Version(1);
        $publishedWiki = new Wiki(
            $publishedWikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $version,
        );

        return new CreateWikiTestData(
            $publishedWikiIdentifier,
            $editorIdentifier,
            $language,
            $resourceType,
            $basic,
            $sections,
            $themeColor,
            $slug,
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
            $wikiIdentifier,
            $translationSetIdentifier,
            $status,
            $draftWiki,
            $publishedWiki,
            $version,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class CreateWikiTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        public WikiIdentifier            $publishedWikiIdentifier,
        public PrincipalIdentifier       $editorIdentifier,
        public Language                  $language,
        public ResourceType              $resourceType,
        public GroupBasic                $basic,
        public SectionContentCollection  $sections,
        public ?Color                    $themeColor,
        public Slug                      $slug,
        public ?WikiIdentifier           $agencyIdentifier,
        public array                     $groupIdentifiers,
        public array                     $talentIdentifiers,
        public DraftWikiIdentifier       $wikiIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public ApprovalStatus            $status,
        public DraftWiki                 $draftWiki,
        public Wiki                      $publishedWiki,
        public Version                   $version,
    ) {
    }
}
