<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInput;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\TestCase;

class ListManagedImagesInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListManagedImagesInput(
            status: ApprovalStatus::Approved,
            principalIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f101'),
        );

        $this->assertSame(ApprovalStatus::Approved, $input->status());
        $this->assertSame(10, $input->perPage());
        $this->assertNull($input->license());
        $this->assertFalse($input->authorMissing());
        $this->assertFalse($input->licenseMissing());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', (string) $input->principalIdentifier());
    }

    public function testAccessors(): void
    {
        $input = new ListManagedImagesInput(
            status: ApprovalStatus::Approved,
            principalIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f102'),
            perPage: 20,
            license: ImageLicense::CC_BY,
            authorMissing: true,
            licenseMissing: true,
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame(ImageLicense::CC_BY, $input->license());
        $this->assertTrue($input->authorMissing());
        $this->assertTrue($input->licenseMissing());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f102', (string) $input->principalIdentifier());
    }
}
