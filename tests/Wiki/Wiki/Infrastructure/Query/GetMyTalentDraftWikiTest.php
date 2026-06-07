<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyTalentDraftWiki\GetMyTalentDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyTalentDraftWiki\GetMyTalentDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class GetMyTalentDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsCurrentEditorsDraftTalentWiki(): void
    {
        $editorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03');
        $otherEditorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a201', $editorIdentifier, 'My Talent');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a202', $otherEditorIdentifier, 'Other Talent');

        $useCase = $this->app->make(GetMyTalentDraftWikiInterface::class);
        $readModel = $useCase->process(new GetMyTalentDraftWikiInput(
            new Slug('tl-my-talent'),
            Language::KOREAN,
            $editorIdentifier,
        ));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217a201', $readModel->wikiIdentifier());
        $this->assertSame('My Talent', $readModel->basic()['name']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenOnlyOtherEditorsDraftTalentWikiExists(): void
    {
        $this->createDraft(
            '01965bb2-bcc9-7c6f-8b90-89f7f217a203',
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff'),
            'Other Talent',
        );

        $useCase = $this->app->make(GetMyTalentDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetMyTalentDraftWikiInput(
            new Slug('tl-my-talent'),
            Language::KOREAN,
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03'),
        ));
    }

    private function createDraft(string $wikiIdentifier, PrincipalIdentifier $editorIdentifier, string $name): void
    {
        CreateDraftWiki::create(
            $wikiIdentifier,
            'talent',
            [
                'translation_set_identifier' => str_replace('a2', 'b2', $wikiIdentifier),
                'slug' => 'tl-my-talent',
                'language' => 'ko',
                'editor_id' => (string) $editorIdentifier,
            ],
            [
                'name' => $name,
                'normalized_name' => strtolower(str_replace(' ', '-', $name)),
            ],
        );
    }
}
