<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInput;
use Tests\TestCase;

class GetGroupWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('gr-twice');
        $language = Language::KOREAN;
        $input = new GetGroupWikiInput($slug, $language);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
    }
}
