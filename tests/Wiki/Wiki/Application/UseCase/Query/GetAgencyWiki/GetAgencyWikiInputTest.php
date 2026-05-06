<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInput;
use Tests\TestCase;

class GetAgencyWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('ag-jyp-entertainment');
        $language = Language::KOREAN;
        $input = new GetAgencyWikiInput($slug, $language);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
    }
}
