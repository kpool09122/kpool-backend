<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Database\Seeders\WikiEditorSampleSeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInterface;
use Tests\TestCase;

class GetTalentDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDraftTalentWikiForSeederData(): void
    {
        $this->seed(WikiEditorSampleSeeder::class);

        $useCase = $this->app->make(GetTalentDraftWikiInterface::class);
        $readModel = $useCase->process(new GetTalentDraftWikiInput(new Slug('chaeyoung'), Language::KOREAN));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f172', $readModel->wikiIdentifier());
        $this->assertSame('chaeyoung', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('talent', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('Chaeyoung', $readModel->basic()['name']);
        $this->assertSame('Son Chaeyoung', $readModel->basic()['realName']);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f001', $readModel->basic()['groups'][0]['wikiIdentifier']);
        $this->assertSame('TWICE', $readModel->basic()['groups'][0]['name']);
        $this->assertSame('girl_group', $readModel->basic()['groups'][0]['groupType']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftTalentWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetTalentDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetTalentDraftWikiInput(new Slug('chaeyoung'), Language::KOREAN));
    }
}
