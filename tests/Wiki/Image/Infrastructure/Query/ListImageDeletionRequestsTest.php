<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInput;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateImageDeletionRequest;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListImageDeletionRequestsTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsPendingDeletionRequestsWithRequesterDetails(): void
    {
        $principalIdentifier = $this->bindPrincipalRepository();
        $this->setPolicyEvaluatorResult(true);

        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f448f101', [
            'resource_type' => 'talent',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f448f901',
            'image_path' => 'images/talents/deletion-request.jpg',
            'display_order' => 3,
            'source_url' => 'https://example.com/source-image',
            'source_name' => 'Source Name',
            'alt_text' => 'Deletion request target',
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateImageDeletionRequest::create('01965bb2-bcc9-7c6f-8b90-89f7f448d101', [
            'image_id' => '01965bb2-bcc9-7c6f-8b90-89f7f448f101',
            'requester_name' => 'Request User',
            'requester_email' => 'request-user@example.com',
            'reason' => '肖像権の懸念があるため',
            'requested_at' => '2026-05-02 00:00:00',
        ]);

        $payload = $this->process(new ListImageDeletionRequestsInput($principalIdentifier))->toArray();

        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(1, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f448f101',
            'url' => 'http://127.0.0.1:8080/storage/images/talents/deletion-request.jpg',
            'resourceType' => 'talent',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f448f901',
            'displayOrder' => 3,
            'sourceUrl' => 'https://example.com/source-image',
            'sourceName' => 'Source Name',
            'altText' => 'Deletion request target',
            'isHidden' => false,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
            'name' => 'Request User',
            'email' => 'request-user@example.com',
            'reason' => '肖像権の懸念があるため',
        ], $payload['images'][0]);
    }

    #[Group('useDb')]
    public function testProcessExcludesReviewedDeletionRequests(): void
    {
        $principalIdentifier = $this->bindPrincipalRepository();
        $this->setPolicyEvaluatorResult(true);

        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f448f201', [
            'uploaded_at' => '2026-05-01 00:00:00',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f448f202', [
            'uploaded_at' => '2026-05-02 00:00:00',
        ]);
        CreateImageDeletionRequest::create('01965bb2-bcc9-7c6f-8b90-89f7f448d201', [
            'image_id' => '01965bb2-bcc9-7c6f-8b90-89f7f448f201',
            'reviewed_at' => null,
        ]);
        CreateImageDeletionRequest::create('01965bb2-bcc9-7c6f-8b90-89f7f448d202', [
            'image_id' => '01965bb2-bcc9-7c6f-8b90-89f7f448f202',
            'reviewer_id' => StrTestHelper::generateUuid(),
            'reviewed_at' => '2026-05-03 00:00:00',
            'reviewer_comment' => '対応済み',
        ]);

        $payload = $this->process(new ListImageDeletionRequestsInput($principalIdentifier))->toArray();

        $this->assertSame(1, $payload['total']);
        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f448f201'], array_column($payload['images'], 'imageIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessAppliesPagination(): void
    {
        $principalIdentifier = $this->bindPrincipalRepository();
        $this->setPolicyEvaluatorResult(true);

        foreach ([1, 2, 3] as $index) {
            $imageId = sprintf('01965bb2-bcc9-7c6f-8b90-89f7f448f30%d', $index);
            CreateImage::create($imageId, [
                'uploaded_at' => sprintf('2026-05-0%d 00:00:00', $index),
            ]);
            CreateImageDeletionRequest::create(sprintf('01965bb2-bcc9-7c6f-8b90-89f7f448d30%d', $index), [
                'image_id' => $imageId,
                'requested_at' => sprintf('2026-05-0%d 00:00:00', $index),
            ]);
        }

        $payload = $this->process(new ListImageDeletionRequestsInput($principalIdentifier, 2))->toArray();

        $this->assertCount(2, $payload['images']);
        $this->assertSame(2, $payload['per_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame([
            '01965bb2-bcc9-7c6f-8b90-89f7f448f303',
            '01965bb2-bcc9-7c6f-8b90-89f7f448f302',
        ], array_column($payload['images'], 'imageIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessDisallowedWhenPrincipalCannotApproveOrReject(): void
    {
        $principalIdentifier = $this->bindPrincipalRepository();
        $this->setPolicyEvaluatorResult(false);

        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f448f401');
        CreateImageDeletionRequest::create('01965bb2-bcc9-7c6f-8b90-89f7f448d401', [
            'image_id' => '01965bb2-bcc9-7c6f-8b90-89f7f448f401',
        ]);

        $this->expectException(DisallowedException::class);
        $this->process(new ListImageDeletionRequestsInput($principalIdentifier));
    }

    private function listImageDeletionRequests(): ListImageDeletionRequestsInterface
    {
        return $this->app->make(ListImageDeletionRequestsInterface::class);
    }

    private function process(ListImageDeletionRequestsInput $input): ListImageDeletionRequestsOutput
    {
        $output = new ListImageDeletionRequestsOutput();
        $this->listImageDeletionRequests()->process($input, $output);

        return $output;
    }

    private function bindPrincipalRepository(): PrincipalIdentifier
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->andReturn($principal);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        return $principalIdentifier;
    }
}
