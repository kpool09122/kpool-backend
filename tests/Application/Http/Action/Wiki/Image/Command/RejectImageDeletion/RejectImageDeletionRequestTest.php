<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Wiki\Image\Command\RejectImageDeletion;

use Application\Http\Action\Wiki\Image\Command\RejectImageDeletion\RejectImageDeletionRequest;
use PHPUnit\Framework\TestCase;

class RejectImageDeletionRequestTest extends TestCase
{
    /**
     * 正常系: rejectReason が必須であること.
     */
    public function testRejectReasonIsRequired(): void
    {
        $request = new RejectImageDeletionRequest();

        $this->assertSame(['required', 'string'], $request->rules()['rejectReason']);
    }
}
