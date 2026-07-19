<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests;

use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\TestCase;

class ListImageDeletionRequestsInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListImageDeletionRequestsInput(
            principalIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f101'),
        );

        $this->assertSame(10, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', (string) $input->principalIdentifier());
    }

    public function testAccessors(): void
    {
        $input = new ListImageDeletionRequestsInput(
            principalIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f102'),
            perPage: 20,
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f102', (string) $input->principalIdentifier());
    }
}
