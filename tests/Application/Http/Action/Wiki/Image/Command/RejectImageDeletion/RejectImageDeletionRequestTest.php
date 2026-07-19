<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Wiki\Image\Command\RejectImageDeletion;

use Application\Http\Action\Wiki\Image\Command\RejectImageDeletion\RejectImageDeletionRequest;
use PHPUnit\Framework\TestCase;

class RejectImageDeletionRequestTest extends TestCase
{
    /**
     * 正常系: reviewerComment が必須であること.
     */
    public function testReviewerCommentIsRequired(): void
    {
        $request = new RejectImageDeletionRequest();

        $this->assertSame(['required', 'string'], $request->rules()['reviewerComment']);
    }
}
