<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesOutput;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Tests\Helper\CreateDraftImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class ListDraftImagesTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessFiltersByStatusSortedByUploadedAtDesc(): void
    {
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f101', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'status' => ApprovalStatus::UnderReview->value,
            'image_path' => '/images/talents/alpha.jpg',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f102', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'status' => ApprovalStatus::UnderReview->value,
            'image_path' => '/images/talents/beta.jpg',
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f103', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'status' => ApprovalStatus::Pending->value,
            'uploaded_at' => '2026-05-04 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f104', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::UnderReview->value,
            'uploaded_at' => '2026-05-05 00:00:00',
        ]);

        $payload = $this->process(new ListDraftImagesInput(
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
        ], array_column($payload['images'], 'imageIdentifier'));
        $this->assertSame('http://localhost:8080/images/test/sample.jpg', $payload['images'][0]['url']);
        $this->assertSame([
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
            ApprovalStatus::UnderReview->value,
        ], array_column($payload['images'], 'status'));
    }

    #[Group('useDb')]
    public function testProcessFiltersByTranslationSetIdentifierWhenSpecified(): void
    {
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f301', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::UnderReview->value,
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f302', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'status' => ApprovalStatus::UnderReview->value,
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f303', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'status' => ApprovalStatus::Pending->value,
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftImagesInput(
            status: ApprovalStatus::UnderReview,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f401'),
        ))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
        ], array_column($payload['images'], 'imageIdentifier'));
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
        ], array_column($payload['images'], 'translationSetIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessAppliesPerPage(): void
    {
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f201', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::Pending->value,
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f202', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::Pending->value,
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f203', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'status' => ApprovalStatus::Pending->value,
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListDraftImagesInput(
            status: ApprovalStatus::Pending,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f902'),
            perPage: 2,
        ))->toArray();

        $this->assertCount(2, $payload['images']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
    }

    #[Group('useDb')]
    public function testProcessReturnsDraftImageMetadata(): void
    {
        CreateDraftImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f501', [
            'published_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'resource_type' => 'group',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'image_path' => 'images/groups/cover.jpg',
            'display_order' => 2,
            'source_url' => 'https://example.com/source-image',
            'source_name' => 'Source Name',
            'alt_text' => 'Cover image',
            'status' => ApprovalStatus::Approved->value,
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f701', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ko',
        ], [
            'name' => 'TWICE',
        ]);
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f702', 'group', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'slug' => 'gr-twice',
            'language' => 'ja',
        ], [
            'name' => 'トゥワイス',
        ]);

        $payload = $this->process(new ListDraftImagesInput(
            status: ApprovalStatus::Approved,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f601'),
        ))->toArray();

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f501',
            'publishedImageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'url' => 'http://localhost:8080/storage/images/groups/cover.jpg',
            'resourceType' => 'group',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'displayOrder' => 2,
            'sourceUrl' => 'https://example.com/source-image',
            'sourceName' => 'Source Name',
            'altText' => 'Cover image',
            'wiki' => [
                'names' => [
                    'ko' => 'TWICE',
                    'ja' => 'トゥワイス',
                ],
                'slug' => 'gr-twice',
            ],
            'status' => ApprovalStatus::Approved->value,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
        ], $payload['images'][0]);
    }

    private function listDraftImages(): ListDraftImagesInterface
    {
        return $this->app->make(ListDraftImagesInterface::class);
    }

    private function process(ListDraftImagesInput $input): ListDraftImagesOutput
    {
        $output = new ListDraftImagesOutput();
        $this->listDraftImages()->process($input, $output);

        return $output;
    }
}
