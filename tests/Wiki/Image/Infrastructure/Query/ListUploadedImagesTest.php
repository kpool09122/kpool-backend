<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesOutput;
use Tests\Helper\CreateImage;
use Tests\TestCase;

class ListUploadedImagesTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDefaultPaginationSortedByUploadedAtDesc(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f101', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_path' => '/images/talents/alpha.jpg',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f102', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_path' => '/images/talents/beta.jpg',
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f103', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f901',
            'image_path' => '/images/talents/gamma.jpg',
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);

        $payload = $this->process(new ListUploadedImagesInput(
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f901'),
        ))->toArray();

        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        ], array_column($payload['images'], 'imageIdentifier'));
        $this->assertSame('http://localhost:8080/images/talents/beta.jpg', $payload['images'][0]['url']);
    }

    #[Group('useDb')]
    public function testProcessAppliesPerPage(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f201', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f202', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f203', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f902',
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListUploadedImagesInput(
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f902'),
            perPage: 2,
        ))->toArray();

        $this->assertCount(2, $payload['images']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByTranslationSetIdentifier(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f301', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f302', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f303', [
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'uploaded_at' => '2026-05-03 00:00:00',
        ]);

        $payload = $this->process(new ListUploadedImagesInput(
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f401'),
        ))->toArray();

        $this->assertSame(2, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f303',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
        ], array_column($payload['images'], 'imageIdentifier'));
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
        ], array_column($payload['images'], 'translationSetIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessReturnsImageMetadata(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f501', [
            'resource_type' => 'group',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'image_path' => 'images/groups/cover.jpg',
            'display_order' => 2,
            'source_url' => 'https://example.com/source-image',
            'source_name' => 'Source Name',
            'alt_text' => 'Cover image',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);

        $payload = $this->process(new ListUploadedImagesInput(
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f601'),
        ))->toArray();

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f501',
            'url' => 'http://localhost:8080/storage/images/groups/cover.jpg',
            'resourceType' => 'group',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f601',
            'displayOrder' => 2,
            'sourceUrl' => 'https://example.com/source-image',
            'sourceName' => 'Source Name',
            'altText' => 'Cover image',
            'isHidden' => false,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
        ], $payload['images'][0]);
    }

    private function listUploadedImages(): ListUploadedImagesInterface
    {
        return $this->app->make(ListUploadedImagesInterface::class);
    }

    private function process(ListUploadedImagesInput $input): ListUploadedImagesOutput
    {
        $output = new ListUploadedImagesOutput();
        $this->listUploadedImages()->process($input, $output);

        return $output;
    }
}
