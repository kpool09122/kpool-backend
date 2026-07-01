<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class GetMyAgencyDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsCurrentEditorsDraftAgencyWiki(): void
    {
        $editorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03');
        $otherEditorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a401', $editorIdentifier, 'My Agency', '事務所情報を補足してください');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a402', $otherEditorIdentifier, 'Other Agency');

        $useCase = $this->app->make(GetMyAgencyDraftWikiInterface::class);
        $readModel = $useCase->process(new GetMyAgencyDraftWikiInput(
            new Slug('ag-my-agency'),
            Language::KOREAN,
            $editorIdentifier,
        ));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217a401', $readModel->wikiIdentifier());
        $this->assertSame('My Agency', $readModel->basic()['name']);
        $this->assertSame('事務所情報を補足してください', $readModel->rejectionReason());
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenOnlyOtherEditorsDraftAgencyWikiExists(): void
    {
        $this->createDraft(
            '01965bb2-bcc9-7c6f-8b90-89f7f217a403',
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff'),
            'Other Agency',
        );

        $useCase = $this->app->make(GetMyAgencyDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetMyAgencyDraftWikiInput(
            new Slug('ag-my-agency'),
            Language::KOREAN,
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03'),
        ));
    }

    private function createDraft(
        string $wikiIdentifier,
        PrincipalIdentifier $editorIdentifier,
        string $name,
        ?string $rejectionReason = null,
    ): void {
        CreateDraftWiki::create(
            $wikiIdentifier,
            'agency',
            [
                'translation_set_identifier' => str_replace('a4', 'b4', $wikiIdentifier),
                'slug' => 'ag-my-agency',
                'language' => 'ko',
                'editor_id' => (string) $editorIdentifier,
                'rejection_reason' => $rejectionReason,
            ],
            [
                'name' => $name,
                'normalized_name' => strtolower(str_replace(' ', '-', $name)),
            ],
        );
    }
}
