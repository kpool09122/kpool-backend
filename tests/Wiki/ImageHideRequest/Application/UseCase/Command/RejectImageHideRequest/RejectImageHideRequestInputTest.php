<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest\RejectImageHideRequestInput;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageHideRequestInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $reviewerComment = 'Rejected for invalid reason.';

        $input = new RejectImageHideRequestInput(
            $requestIdentifier,
            $principalIdentifier,
            $reviewerComment,
        );

        $this->assertSame((string) $requestIdentifier, (string) $input->requestIdentifier());
        $this->assertSame((string) $principalIdentifier, (string) $input->principalIdentifier());
        $this->assertSame($reviewerComment, $input->reviewerComment());
    }
}
