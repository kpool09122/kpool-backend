<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Database\Seeders\WikiEditorSampleSeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetGroupDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDraftGroupWikiForSeederData(): void
    {
        $this->seed(WikiEditorSampleSeeder::class);

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetGroupDraftWikiInput(new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f002')));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f003', $readModel->translationSetIdentifier());
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame('pending', $readModel->status());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null, 'src' => null, 'alt' => null], $readModel->heroImage());
        $this->assertInstanceOf(GroupWikiBasicReadModel::class, $readModel->basic());
        $this->assertSame('TWICE', $readModel->basic()['name']);
        $this->assertSame('girl_group', $readModel->basic()['groupType']);
        $this->assertSame([['colorCode' => '#FE5F8F', 'label' => 'Apricot'], ['colorCode' => '#FEE500', 'label' => 'Yellow']], $readModel->basic()['officialColors']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessReturnsNullableOptionalGroupBasicValues(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'agency',
            [
                'slug' => 'ag-jyp',
                'language' => 'ko',
            ],
            [
                'name' => 'JYP Entertainment',
                'normalized_name' => 'jyp entertainment',
            ],
        );
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
                'slug' => 'gr-nullable-basic',
                'language' => 'en',
                'rejection_reason' => 'グループ基本情報が不足しています',
            ],
            [
                'name' => 'Test',
                'normalized_name' => '',
                'agency_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
                'group_type' => null,
                'generation' => null,
            ],
        );

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetGroupDraftWikiInput(new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f102')));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $readModel->basic()['agencyIdentifier']);
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'slug' => 'ag-jyp',
            'language' => 'ko',
            'name' => 'JYP Entertainment',
            'normalizedName' => 'jyp entertainment',
        ], $readModel->basic()['agency']);
        $this->assertNull($readModel->basic()['groupType']);
        $this->assertNull($readModel->basic()['generation']);
        $this->assertSame('グループ基本情報が不足しています', $readModel->rejectionReason());
    }

    #[Group('useDb')]
    public function testProcessReturnsSpecifiedDraftWhenSameSlugAndLanguageExist(): void
    {
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f203',
                'slug' => 'gr-duplicated',
                'language' => 'ko',
            ],
            [
                'name' => 'First',
                'normalized_name' => 'first',
            ],
        );
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f204',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f205',
                'slug' => 'gr-duplicated',
                'language' => 'ko',
            ],
            [
                'name' => 'Second',
                'normalized_name' => 'second',
            ],
        );

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetGroupDraftWikiInput(new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f204')));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f204', $readModel->wikiIdentifier());
        $this->assertSame('Second', $readModel->basic()['name']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftGroupWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetGroupDraftWikiInput(new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff')));
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftWikiResourceTypeIsDifferent(): void
    {
        $this->seed(WikiEditorSampleSeeder::class);

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetGroupDraftWikiInput(new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f172')));
    }
}
