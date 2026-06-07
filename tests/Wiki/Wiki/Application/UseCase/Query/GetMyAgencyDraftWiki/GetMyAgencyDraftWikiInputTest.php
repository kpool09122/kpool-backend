<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInput;
use Tests\TestCase;

class GetMyAgencyDraftWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('ag-jyp-entertainment');
        $language = Language::KOREAN;
        $editorIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217fa03');

        $input = new GetMyAgencyDraftWikiInput($slug, $language, $editorIdentifier);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
        $this->assertSame((string) $editorIdentifier, (string) $input->editorIdentifier());
    }
}
