<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInput;
use Tests\TestCase;

class ListVersionInconsistentWikisInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListVersionInconsistentWikisInput();

        $this->assertSame(10, $input->perPage());
        $this->assertNull($input->resourceType());
        $this->assertSame('updatedAt', $input->sort());
        $this->assertSame('desc', $input->order());
    }

    public function testAccessors(): void
    {
        $input = new ListVersionInconsistentWikisInput(
            perPage: 20,
            resourceType: ResourceType::TALENT,
            sort: 'name',
            order: 'asc',
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame(ResourceType::TALENT, $input->resourceType());
        $this->assertSame('name', $input->sort());
        $this->assertSame('asc', $input->order());
    }
}
