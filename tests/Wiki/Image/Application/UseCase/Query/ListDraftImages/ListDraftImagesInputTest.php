<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInput;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\TestCase;

class ListDraftImagesInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListDraftImagesInput(
            status: ApprovalStatus::UnderReview,
        );

        $this->assertSame(10, $input->perPage());
        $this->assertNull($input->wikiIdentifier());
        $this->assertSame(ApprovalStatus::UnderReview, $input->status());
    }

    public function testAccessors(): void
    {
        $input = new ListDraftImagesInput(
            status: ApprovalStatus::Pending,
            wikiIdentifier: new WikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f102'),
            perPage: 20,
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f102', (string) $input->wikiIdentifier());
        $this->assertSame(ApprovalStatus::Pending, $input->status());
    }
}
