<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\EditWiki;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\EditWiki\EditWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
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

class EditWikiTest extends TestCase
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
        $editWiki = $this->app->make(EditWikiInterface::class);
        $this->assertInstanceOf(EditWiki::class, $editWiki);
    }

    /**
     * 正常系：正しくDraftWiki Entityが編集されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyEditWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $updatedBasic = new GroupBasic(
            name: new Name('ITZY'),
            normalizedName: 'itzy',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('MIDZY'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
        $updatedSections = new SectionContentCollection();
        $updatedThemeColor = new Color('#00FF00');

        $input = new EditWikiInput(
            $testData->wikiIdentifier,
            $updatedBasic,
            $updatedSections,
            $updatedThemeColor,
            $principalIdentifier,
            $testData->resourceType,
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
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->wikiIdentifier)
            ->andReturn($testData->draftWiki);
        $draftWikiRepository->shouldReceive('save')
            ->once()
            ->with($testData->draftWiki)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $editWiki = $this->app->make(EditWikiInterface::class);
        $output = new EditWikiOutput();
        $editWiki->process($input, $output);
        $result = $output->toArray();

        $this->assertSame($testData->language->value, $result['language']);
        $this->assertSame((string) $updatedBasic->name(), $result['name']);
        $this->assertSame($testData->resourceType->value, $result['resourceType']);
        $this->assertSame($testData->status->value, $result['status']);
    }

    /**
     * 異常系：指定したIDに紐づくDraftWikiが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundWiki(): void
    {
        $testData = $this->createDummyEditWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditWikiInput(
            $testData->wikiIdentifier,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $principalIdentifier,
            $testData->resourceType,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->wikiIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $editWiki = $this->app->make(EditWikiInterface::class);
        $editWiki->process($input, new EditWikiOutput());
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $testData = $this->createDummyEditWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditWikiInput(
            $testData->wikiIdentifier,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $principalIdentifier,
            $testData->resourceType,
            $testData->agencyIdentifier,
            $testData->groupIdentifiers,
            $testData->talentIdentifiers,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->wikiIdentifier)
            ->andReturn($testData->draftWiki);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $editWiki = $this->app->make(EditWikiInterface::class);
        $editWiki->process($input, new EditWikiOutput());
    }

    /**
     * 正常系：権限を持つユーザーがWikiを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorized(): void
    {
        $testData = $this->createDummyEditWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditWikiInput(
            $testData->wikiIdentifier,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $principalIdentifier,
            $testData->resourceType,
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
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->wikiIdentifier)
            ->andReturn($testData->draftWiki);
        $draftWikiRepository->shouldReceive('save')
            ->once()
            ->with($testData->draftWiki)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $editWiki = $this->app->make(EditWikiInterface::class);
        $editWiki->process($input, new EditWikiOutput());
    }

    /**
     * 異常系：権限を持たないユーザーがWikiを編集しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorized(): void
    {
        $testData = $this->createDummyEditWiki();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditWikiInput(
            $testData->wikiIdentifier,
            $testData->basic,
            $testData->sections,
            $testData->themeColor,
            $principalIdentifier,
            $testData->resourceType,
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
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')
            ->once()
            ->with($testData->wikiIdentifier)
            ->andReturn($testData->draftWiki);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(DisallowedException::class);
        $editWiki = $this->app->make(EditWikiInterface::class);
        $editWiki->process($input, new EditWikiOutput());
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return EditWikiTestData
     */
    private function createDummyEditWiki(): EditWikiTestData
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $publishedWikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
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

        return new EditWikiTestData(
            $wikiIdentifier,
            $publishedWikiIdentifier,
            $translationSetIdentifier,
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
            $status,
            $draftWiki,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class EditWikiTestData
{
    /**
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
        public GroupBasic               $basic,
        public SectionContentCollection $sections,
        public ?Color                   $themeColor,
        public Slug                     $slug,
        public ?WikiIdentifier          $agencyIdentifier,
        public array                    $groupIdentifiers,
        public array                    $talentIdentifiers,
        public ApprovalStatus           $status,
        public DraftWiki                $draftWiki,
    ) {
    }
}
