<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMySongDraftWiki\GetMySongDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMySongDraftWiki\GetMySongDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class GetMySongDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsCurrentEditorsDraftSongWiki(): void
    {
        $editorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03');
        $otherEditorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a301', $editorIdentifier, 'My Song');
        $this->createDraft('01965bb2-bcc9-7c6f-8b90-89f7f217a302', $otherEditorIdentifier, 'Other Song');

        $useCase = $this->app->make(GetMySongDraftWikiInterface::class);
        $readModel = $useCase->process(new GetMySongDraftWikiInput(
            new Slug('sg-my-song'),
            Language::KOREAN,
            $editorIdentifier,
        ));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217a301', $readModel->wikiIdentifier());
        $this->assertSame('My Song', $readModel->basic()['name']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenOnlyOtherEditorsDraftSongWikiExists(): void
    {
        $this->createDraft(
            '01965bb2-bcc9-7c6f-8b90-89f7f217a303',
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217ffff'),
            'Other Song',
        );

        $useCase = $this->app->make(GetMySongDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetMySongDraftWikiInput(
            new Slug('sg-my-song'),
            Language::KOREAN,
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03'),
        ));
    }

    private function createDraft(string $wikiIdentifier, PrincipalIdentifier $editorIdentifier, string $name): void
    {
        CreateDraftWiki::create(
            $wikiIdentifier,
            'song',
            [
                'translation_set_identifier' => str_replace('a3', 'b3', $wikiIdentifier),
                'slug' => 'sg-my-song',
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
