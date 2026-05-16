<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutput;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListDraftWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessFiltersByStatusSortedByEditedAtDesc(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f101', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ], [
            'name' => 'Alpha',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f102', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-03 00:00:00',
        ], [
            'name' => 'Beta',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f103', 'agency', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-04 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f104', 'song', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f903',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-05 00:00:00',
        ], [
            'name' => 'Gamma',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::UnderReview,
        ))->toArray();

        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
        ], array_column($payload['wikis'], 'status'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByTranslationSetIdentifierWhenSpecified(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f202', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f203', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::UnderReview,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f401'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
        ], array_column($payload['wikis'], 'translationSetIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByResourceTypeWhenSpecified(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f301', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f302', 'group', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f303', 'song', [
            'status' => ApprovalStatus::UnderReview->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::UnderReview,
            resourceType: ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f302',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([ResourceType::GROUP->value], array_column($payload['wikis'], 'resourceType'));
    }

    #[Group('useDb')]
    public function testProcessAppliesPerPage(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f401', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f402', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f403', 'talent', [
            'status' => ApprovalStatus::Pending->value,
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::Pending,
            perPage: 2,
        ))->toArray();

        $this->assertCount(2, $payload['wikis']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByEditorIdentifierWhenSpecified(): void
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

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::UnderReview,
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessReturnsDraftWikiMetadata(): void
    {
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f502', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
        ]);

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f501', 'group', [
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'theme_color' => '#ff3366',
            'status' => ApprovalStatus::Approved->value,
            'edited_at' => '2026-05-01 00:00:00',
            'approved_at' => '2026-05-02 00:00:00',
            'translated_at' => '2026-05-03 00:00:00',
            'merged_at' => '2026-05-04 00:00:00',
            'updated_at' => '2026-05-05 00:00:00',
        ], [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
        ]);

        $payload = $this->process(new ListDraftWikisInput(
            status: ApprovalStatus::Approved,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f601'),
        ))->toArray();

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f501',
            'publishedWikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'resourceType' => 'group',
            'themeColor' => '#ff3366',
            'status' => ApprovalStatus::Approved->value,
            'name' => 'TWICE',
            'normalizedName' => 'twice',
            'editedAt' => '2026-05-01T00:00:00+00:00',
            'updatedAt' => '2026-05-05T00:00:00+00:00',
            'approvedAt' => '2026-05-02T00:00:00+00:00',
            'translatedAt' => '2026-05-03T00:00:00+00:00',
            'mergedAt' => '2026-05-04T00:00:00+00:00',
        ], $payload['wikis'][0]);
    }

    private function listDraftWikis(): ListDraftWikisInterface
    {
        return $this->app->make(ListDraftWikisInterface::class);
    }

    private function process(ListDraftWikisInput $input): ListDraftWikisOutput
    {
        $output = new ListDraftWikisOutput();
        $this->listDraftWikis()->process($input, $output);

        return $output;
    }
}
