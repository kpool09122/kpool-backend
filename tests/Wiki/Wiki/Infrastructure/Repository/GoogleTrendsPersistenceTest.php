<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentMapper;
use Tests\Helper\CreateDraftReadPrincipal;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GoogleTrendsPersistenceTest extends TestCase
{
    #[Group('useDb')]
    #[TestWith(['JP', 'today 12-m'])]
    #[TestWith(['KR', 'today 5-y'])]
    #[TestWith(['all', 'today 12-m'])]
    public function testDraftAndPublishedPersistenceAndQueriesPreserveSettings(string $region, string $timeRange): void
    {
        $data = [['type' => 'section', 'sectionKey' => 'trends-section', 'title' => 'Trends', 'displayOrder' => 4, 'contents' => [[
            'type' => 'google_trends', 'blockKey' => 'trends-block', 'displayOrder' => 2,
            'searchTerm' => '少女時代 & Girls Generation', 'region' => $region, 'timeRange' => $timeRange,
        ]]]];
        $publishedId = new WikiIdentifier(StrTestHelper::generateUuid());
        CreateWiki::create((string) $publishedId, 'group', ['slug' => 'gr-trends', 'language' => 'ja']);
        $wikiRepository = $this->app()->make(WikiRepositoryInterface::class);
        $wiki = $wikiRepository->findById($publishedId);
        self::assertNotNull($wiki);
        $wiki->setSections(SectionContentMapper::collectionFromArray($data));
        $wikiRepository->save($wiki);
        $restoredWiki = $wikiRepository->findById($publishedId);
        self::assertNotNull($restoredWiki);
        self::assertSame($data, SectionContentMapper::collectionToArray($restoredWiki->sections()));
        $published = $this->app()->make(GetGroupWikiInterface::class)->process(new GetGroupWikiInput(new Slug('gr-trends'), Language::JAPANESE));
        self::assertEquals($data, $published->sections());

        $draftId = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        CreateDraftWiki::create((string) $draftId, 'group');
        $draftWikiRepository = $this->app()->make(DraftWikiRepositoryInterface::class);
        $draft = $draftWikiRepository->findById($draftId);
        self::assertNotNull($draft);
        $draft->setSections(SectionContentMapper::collectionFromArray($data));
        $draftWikiRepository->save($draft);
        $restoredDraft = $draftWikiRepository->findById($draftId);
        self::assertNotNull($restoredDraft);
        self::assertSame($data, SectionContentMapper::collectionToArray($restoredDraft->sections()));
        $readModel = $this->app()->make(GetGroupDraftWikiInterface::class)->process(new GetGroupDraftWikiInput($draftId, CreateDraftReadPrincipal::create()));
        self::assertEquals($data, $readModel->sections());
    }
}
