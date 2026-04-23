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
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('TWICE', $readModel->basic()['name']);
        $this->assertSame('girl_group', $readModel->basic()['groupType']);
        $this->assertSame(['#FE5F8F', '#FEE500'], $readModel->basic()['officialColors']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftGroupWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetGroupDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetGroupDraftWikiInput(new Slug('gr-twice'), Language::KOREAN));
    }
}
