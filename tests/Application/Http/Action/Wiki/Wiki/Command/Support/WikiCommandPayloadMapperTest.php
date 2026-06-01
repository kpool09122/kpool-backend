<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Wiki\Wiki\Command\Support;

use Application\Http\Action\Wiki\Wiki\Command\Support\WikiCommandPayloadMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
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

    public function testSectionsMapsNumericTableWidthToDomainTableBlock(): void
    {
        $sections = WikiCommandPayloadMapper::sections([
            [
                'type' => 'section',
                'title' => 'Tables',
                'displayOrder' => 1,
                'contents' => [
                    [
                        'type' => 'table',
                        'displayOrder' => 1,
                        'headerCells' => [['content' => 'Name']],
                        'rowCells' => [[['content' => 'Aurora Echo']]],
                        'tableWidth' => 320,
                    ],
                ],
            ],
        ]);

        $section = $sections->sorted()[0];
        $this->assertInstanceOf(Section::class, $section);
        $block = $section->contents()->sorted()[0];

        $this->assertInstanceOf(TableBlock::class, $block);
        $this->assertSame('320', $block->tableWidth());
    }

    public function testTalentBasicMapsGroupIdentifiersFromDetailSummary(): void
    {
        $basic = WikiCommandPayloadMapper::basic(ResourceType::TALENT, [
            'name' => 'Momo',
            'groups' => [
                ['wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f001'],
            ],
        ]);

        $this->assertInstanceOf(TalentBasic::class, $basic);
        $this->assertSame(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            (string) $basic->groupIdentifiers()[0],
        );
    }

    public function testSongBasicMapsRelatedIdentifiersFromDetailSummaries(): void
    {
        $basic = WikiCommandPayloadMapper::basic(ResourceType::SONG, [
            'name' => 'Fancy',
            'groups' => [
                ['wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f001'],
            ],
            'talents' => [
                ['wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101'],
            ],
        ]);

        $this->assertInstanceOf(SongBasic::class, $basic);
        $this->assertSame(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            (string) $basic->groupIdentifiers()[0],
        );
        $this->assertSame(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            (string) $basic->talentIdentifiers()[0],
        );
    }
}
