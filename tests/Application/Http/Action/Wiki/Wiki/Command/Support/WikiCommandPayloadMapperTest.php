<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Wiki\Wiki\Command\Support;

use Application\Http\Action\Wiki\Wiki\Command\Support\WikiCommandPayloadMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;

class WikiCommandPayloadMapperTest extends TestCase
{
    public function testSectionsMapsCamelCaseApiPayloadToInternalSectionContents(): void
    {
        $sections = WikiCommandPayloadMapper::sections([
            [
                'type' => 'section',
                'title' => 'Overview',
                'displayOrder' => 1,
                'contents' => [
                    [
                        'type' => 'text',
                        'displayOrder' => 1,
                        'content' => 'Hello World',
                    ],
                ],
            ],
        ]);

        $section = $sections->sorted()[0];
        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame(1, $section->displayOrder());

        $block = $section->contents()->sorted()[0];
        $this->assertInstanceOf(TextBlock::class, $block);
        $this->assertSame(1, $block->displayOrder());
        $this->assertSame('Hello World', $block->content());
    }

    public function testSectionsRejectsMissingContentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Section content type is required at sections.0. keys: displayOrder, content.');

        WikiCommandPayloadMapper::sections([
            [
                'displayOrder' => 1,
                'content' => 'Hello World',
            ],
        ]);
    }
}
