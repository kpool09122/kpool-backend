<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiData;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki\TranslateWikiInterface;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateWikiTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     */
    public function test__construct(): void
    {
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $this->app->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $translateWiki = $this->app->make(TranslateWikiInterface::class);
        $this->assertInstanceOf(TranslateWiki::class, $translateWiki);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     */
    public function testProcess(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $principalIdentifier = $testData->principalIdentifier;
        $principal = new Principal(
            $principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            [],
        );

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $principalIdentifier,
            ResourceType::TALENT,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('save')
            ->twice()
            ->andReturn(null);

        $jaTranslatedBasic = $this->createTalentBasic('テスト ウィキ');
        $jaTranslatedSections = new SectionContentCollection();

        $enTranslatedBasic = $this->createTalentBasic('Test Wiki');
        $enTranslatedSections = new SectionContentCollection();

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateWiki')
            ->with($testData->wiki, Language::JAPANESE)
            ->once()
            ->andReturn(new TranslatedWikiData(
                translatedBasic: $jaTranslatedBasic,
                translatedSections: $jaTranslatedSections,
            ));
        $translationService->shouldReceive('translateWiki')
            ->with($testData->wiki, Language::ENGLISH)
            ->once()
            ->andReturn(new TranslatedWikiData(
                translatedBasic: $enTranslatedBasic,
                translatedSections: $enTranslatedSections,
            ));

        $jaDraftWiki = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $testData->translationSetIdentifier,
            $testData->slug,
            Language::JAPANESE,
            ResourceType::TALENT,
            $jaTranslatedBasic,
            $jaTranslatedSections,
            null,
            ApprovalStatus::Pending,
        );

        $enDraftWiki = new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            null,
            $testData->translationSetIdentifier,
            $testData->slug,
            Language::ENGLISH,
            ResourceType::TALENT,
            $enTranslatedBasic,
            $enTranslatedSections,
            null,
            ApprovalStatus::Pending,
        );

        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiFactory->shouldReceive('create')
            ->with(
                Mockery::on(fn ($arg) => $arg === null),
                Language::JAPANESE,
                ResourceType::TALENT,
                $jaTranslatedBasic,
                $testData->slug,
                $testData->translationSetIdentifier,
            )
            ->once()
            ->andReturn($jaDraftWiki);
        $draftWikiFactory->shouldReceive('create')
            ->with(
                Mockery::on(fn ($arg) => $arg === null),
                Language::ENGLISH,
                ResourceType::TALENT,
                $enTranslatedBasic,
                $testData->slug,
                $testData->translationSetIdentifier,
            )
            ->once()
            ->andReturn($enDraftWiki);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);

        $translateWiki = $this->app->make(TranslateWikiInterface::class);
        $wikis = $translateWiki->process($input);

        $this->assertCount(2, $wikis);
        $this->assertInstanceOf(DraftWiki::class, $wikis[0]);
        $this->assertInstanceOf(DraftWiki::class, $wikis[1]);
    }

    /**
     * 異常系：指定したIDのWikiが見つからない場合、例外がスローされること.
     */
    public function testWhenWikiNotFound(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($wikiIdentifier)
            ->once()
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(WikiNotFoundException::class);
        $translateWiki = $this->app->make(TranslateWikiInterface::class);
        $translateWiki->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $testData->principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($testData->principalIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $translateWiki = $this->app->make(TranslateWikiInterface::class);
        $translateWiki->process($input);
    }

    /**
     * 異常系：認可に失敗した場合、例外がスローされること.
     */
    public function testUnauthorizedRole(): void
    {
        $testData = $this->createTranslateWikiTestData();

        $principal = new Principal(
            $testData->principalIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            null,
            [],
            [],
        );

        $input = new TranslateWikiInput(
            $testData->wikiIdentifier,
            $testData->principalIdentifier,
            ResourceType::TALENT,
        );

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->with($testData->wikiIdentifier)
            ->once()
            ->andReturn($testData->wiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($testData->principalIdentifier)
            ->once()
            ->andReturn($principal);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $draftWikiFactory = Mockery::mock(DraftWikiFactoryInterface::class);
        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(DraftWikiFactoryInterface::class, $draftWikiFactory);
        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(DisallowedException::class);
        $translateWiki = $this->app->make(TranslateWikiInterface::class);
        $translateWiki->process($input);
    }

    private function createTalentBasic(string $name): TalentBasic
    {
        return TalentBasic::fromArray([
            'type' => 'talent',
            'name' => $name,
            'normalized_name' => 'test',
            'real_name' => '',
            'normalized_real_name' => '',
            'birthday' => null,
            'agency_identifier' => null,
            'group_identifiers' => [],
            'emoji' => '',
            'representative_symbol' => '',
            'position' => '',
            'fandom_name' => '',
            'profile_image_identifier' => null,
        ]);
    }

    private function createTranslateWikiTestData(): TranslateWikiTestData
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('test-wiki');
        $language = Language::KOREAN;

        $basic = $this->createTalentBasic('테스트 위키');
        $sections = new SectionContentCollection();

        $wiki = new Wiki(
            $wikiIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            ResourceType::TALENT,
            $basic,
            $sections,
            null,
            new Version(1),
            null,
            $editorIdentifier,
        );

        return new TranslateWikiTestData(
            wikiIdentifier: $wikiIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            principalIdentifier: $principalIdentifier,
            editorIdentifier: $editorIdentifier,
            slug: $slug,
            language: $language,
            basic: $basic,
            sections: $sections,
            wiki: $wiki,
        );
    }
}

readonly class TranslateWikiTestData
{
    public function __construct(
        public WikiIdentifier           $wikiIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $principalIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Slug                     $slug,
        public Language                 $language,
        public BasicInterface           $basic,
        public SectionContentCollection $sections,
        public Wiki                     $wiki,
    ) {
    }
}
