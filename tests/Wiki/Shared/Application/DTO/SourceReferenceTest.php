<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Application\DTO;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Application\DTO\SourceReference;

class SourceReferenceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成され、各getterが正しい値を返すこと.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $uri = 'https://example.com/article';
        $title = 'Example Article';

        $sourceReference = new SourceReference($uri, $title);

        $this->assertSame($uri, $sourceReference->uri());
        $this->assertSame($title, $sourceReference->title());
    }
}
