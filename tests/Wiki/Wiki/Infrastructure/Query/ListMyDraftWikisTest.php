<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisOutput;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateImage;
use Tests\TestCase;

class ListMyDraftWikisTest extends TestCase
{
    /** @return array<string, array{?string, ?string}> */
    public static function cursorDates(): array
    {
        return [
            'same dates' => ['2026-05-01 00:00:00', '2026-05-01 00:00:00'],
            'null dates' => [null, null],
            'null before dated items' => [null, '2026-05-01 00:00:00'],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('cursorDates')]
    public function testCursorDoesNotSkipItemsWhenTheLastLoadedItemIsRemoved(?string $date, ?string $remainingDate): void
    {
        $ids = [
            '01965bb2-bcc9-7c6f-8b90-89f7f217f801',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f802',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f803',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f804',
        ];
        foreach (array_reverse($ids) as $id) {
            $itemDate = in_array($id, array_slice($ids, 0, 2), true) ? $date : $remainingDate;
            CreateDraftWiki::create($id, 'talent', [
                'edited_at' => $itemDate,
                'updated_at' => $itemDate,
                'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
                'status' => ApprovalStatus::UnderReview->value,
            ]);
        }

        $first = $this->process(new ListMyDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            perPage: 2,
        ))->toArray();
        $this->assertSame(array_slice($ids, 0, 2), array_column($first['wikis'], 'wikiIdentifier'));
        $this->assertNotNull($first['next_cursor']);

        // The cursor must work even when its original row no longer exists.
        DraftWiki::query()->whereIn('id', array_slice($ids, 0, 2))->delete();

        $second = $this->process(new ListMyDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            perPage: 2,
            cursor: $first['next_cursor'],
        ))->toArray();
        $this->assertSame(array_slice($ids, 2), array_column($second['wikis'], 'wikiIdentifier'));
        $this->assertNull($second['next_cursor']);
        $this->assertSame(2, $second['total']);
    }

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
            statuses: [ApprovalStatus::UnderReview],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByStatuses(): void
    {
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f621', 'talent', [
            'status' => ApprovalStatus::UnderReview->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f622', 'group', [
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f623', 'song', [
            'status' => ApprovalStatus::Approved->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
            'edited_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListMyDraftWikisInput(
            statuses: [ApprovalStatus::UnderReview, ApprovalStatus::Pending],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
        ))->toArray();

        $this->assertSame(2, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f622',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f621',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
        $this->assertSame([
            ApprovalStatus::Pending->value,
            ApprovalStatus::UnderReview->value,
        ], array_column($payload['wikis'], 'status'));
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
            statuses: [ApprovalStatus::Pending],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f711'),
            resourceType: ResourceType::GROUP,
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f611',
        ], array_column($payload['wikis'], 'wikiIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessReturnsDraftWikiImageHiddenStatusWhenImageExists(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f801', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_path' => '/images/test/my-draft-card.jpg',
            'alt_text' => 'My draft wiki card image',
            'is_hidden' => true,
        ]);

        CreateDraftWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f802', 'talent', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f801',
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
        ]);

        $payload = $this->process(new ListMyDraftWikisInput(
            statuses: [ApprovalStatus::Pending],
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f701'),
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f901'),
        ))->toArray();

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f801', $payload['wikis'][0]['imageIdentifier']);
        $this->assertSame('http://127.0.0.1:8080/images/test/my-draft-card.jpg', $payload['wikis'][0]['imageUrl']);
        $this->assertSame('My draft wiki card image', $payload['wikis'][0]['imageAltText']);
        $this->assertTrue($payload['wikis'][0]['isHidden']);
    }

    private function listMyDraftWikis(): ListMyDraftWikisInterface
    {
        return $this->app()->make(ListMyDraftWikisInterface::class);
    }

    private function process(ListMyDraftWikisInput $input): ListMyDraftWikisOutput
    {
        $output = new ListMyDraftWikisOutput();
        $this->listMyDraftWikis()->process($input, $output);

        return $output;
    }
}
