<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyGroupDraftWiki\GetMyGroupDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyGroupDraftWiki\GetMyGroupDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class GetMyGroupDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsCurrentEditorsDraftGroupWiki(): void
    {
        $editorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03');
        $otherEditorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a101', $editorIdentifier, 'My Group', 'グループ情報を補足してください');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a102', $otherEditorIdentifier, 'Other Group');

        $useCase = $this->app->make(GetMyGroupDraftWikiInterface::class);
        $readModel = $useCase->process(new GetMyGroupDraftWikiInput(
            new Slug('gr-my-group'),
            Language::KOREAN,
            $editorIdentifier,
        ));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217a101', $readModel->wikiIdentifier());
        $this->assertSame('My Group', $readModel->basic()['name']);
        $this->assertSame('グループ情報を補足してください', $readModel->rejectionReason());
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenOnlyOtherEditorsDraftGroupWikiExists(): void
    {
        $this->createDraft(
            '01965bb2-bcc9-7c6f-8b90-89f7f217a103',
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff'),
            'Other Group',
        );

        $useCase = $this->app->make(GetMyGroupDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetMyGroupDraftWikiInput(
            new Slug('gr-my-group'),
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
            'group',
            [
                'translation_set_identifier' => str_replace('a1', 'b1', $wikiIdentifier),
                'slug' => 'gr-my-group',
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
