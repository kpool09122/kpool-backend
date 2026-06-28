<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisOutput;
use Tests\Helper\CreateDraftWiki;
use Tests\TestCase;

class ListMyDraftWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessFiltersByEditorIdentifier(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f601', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f602', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f702',
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f603', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListMyDraftWikisInput(
            status: ApprovalStatus::UnderReview,
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessAppliesDraftFilters(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f611', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f711',
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f612', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f711',
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-02 00:00:00',
        ]);

        $payload = $this->process(new ListMyDraftWikisInput(
            status: ApprovalStatus::Pending,
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f711'),
            resourceType: ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f611',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    private function listMyDraftWikis(): ListMyDraftWikisInterface
    {
        return $this->app->make(ListMyDraftWikisInterface::class);
    }

    private function process(ListMyDraftWikisInput $input): ListMyDraftWikisOutput
    {
        $output = new ListMyDraftWikisOutput();
        $this->listMyDraftWikis()->process($input, $output);

        return $output;
    }
}
