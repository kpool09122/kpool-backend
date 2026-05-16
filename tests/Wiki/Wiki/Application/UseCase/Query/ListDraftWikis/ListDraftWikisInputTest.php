<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInput;
use Tests\TestCase;

class ListDraftWikisInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListDraftWikisInput(
            status: ApprovalStatus::UnderReview,
        );

        $this->assertSame(10, $input->perPage());
        $this->assertNull($input->translationSetIdentifier());
        $this->assertSame(ApprovalStatus::UnderReview, $input->status());
        $this->assertNull($input->resourceType());
        $this->assertNull($input->editorIdentifier());
    }

    public function testAccessors(): void
    {
        $input = new ListDraftWikisInput(
            status: ApprovalStatus::Pending,
            translationSetIdentifier: new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f102'),
            resourceType: ResourceType::TALENT,
            editorIdentifier: new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f103'),
            perPage: 20,
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f102', (string) $input->translationSetIdentifier());
        $this->assertSame(ApprovalStatus::Pending, $input->status());
        $this->assertSame(ResourceType::TALENT, $input->resourceType());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f103', (string) $input->editorIdentifier());
    }
}
