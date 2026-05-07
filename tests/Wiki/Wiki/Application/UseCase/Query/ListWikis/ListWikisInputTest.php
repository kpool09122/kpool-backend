<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInput;
use Tests\TestCase;

class ListWikisInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListWikisInput();

        $this->assertSame(10, $input->perPage());
        $this->assertNull($input->resourceType());
        $this->assertNull($input->keyword());
        $this->assertSame('updatedAt', $input->sort());
        $this->assertSame('desc', $input->order());
    }

    public function testAccessors(): void
    {
        $input = new ListWikisInput(
            perPage: 20,
            resourceType: 'talent',
            keyword: 'chae',
            sort: 'name',
            order: 'asc',
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame('talent', $input->resourceType());
        $this->assertSame('chae', $input->keyword());
        $this->assertSame('name', $input->sort());
        $this->assertSame('asc', $input->order());
    }
}
