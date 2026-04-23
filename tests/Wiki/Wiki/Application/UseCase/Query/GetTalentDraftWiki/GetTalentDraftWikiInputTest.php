<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Tests\TestCase;

class GetTalentDraftWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('tl-chaeyoung');
        $language = Language::KOREAN;
        $input = new GetTalentDraftWikiInput($slug, $language);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
    }
}
