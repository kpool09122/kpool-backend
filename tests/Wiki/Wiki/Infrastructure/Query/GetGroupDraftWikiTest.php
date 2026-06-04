<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Database\Seeders\WikiEditorSampleSeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class GetGroupDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDraftGroupWikiForSeederData(): void
    {
        $this->seed(WikiEditorSampleSeeder::class);

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetGroupDraftWikiInput(new Slug('gr-twice'), Language::KOREAN));

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
        $this->assertSame(['#FE5F8F', '#FEE500'], $readModel->basic()['officialColors']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessReturnsNullableOptionalGroupBasicValues(): void
    {
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
                'slug' => 'gr-nullable-basic',
                'language' => 'en',
            ],
            [
                'name' => 'Test',
                'normalized_name' => '',
                'group_type' => null,
                'generation' => null,
            ],
        );

        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetGroupDraftWikiInput(new Slug('gr-nullable-basic'), Language::ENGLISH));

        $this->assertNull($readModel->basic()['groupType']);
        $this->assertNull($readModel->basic()['generation']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftGroupWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetGroupDraftWikiInput(new Slug('gr-twice'), Language::KOREAN));
    }
}
