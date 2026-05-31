<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInput;
use Tests\TestCase;

class ListRelatedProfilesInputTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $slug = new Slug('gr-twice');
        $input = new ListRelatedProfilesInput($slug, Language::KOREAN, ResourceType::TALENT);

        $this->assertSame($slug, $input->slug());
        $this->assertSame(Language::KOREAN, $input->language());
        $this->assertSame(ResourceType::TALENT, $input->resourceType());
    }

    public function testThrowsExceptionWhenResourceTypeIsImage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListRelatedProfilesInput(new Slug('gr-twice'), Language::KOREAN, ResourceType::IMAGE);
    }
}
