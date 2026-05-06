<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetTalentWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsTalentWiki(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            'group',
            [
                'slug' => 'gr-twice',
                'language' => 'ko',
            ],
            [
                'name' => 'TWICE',
                'normalized_name' => 'twice',
                'group_type' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debut_date' => '2015-10-20',
                'fandom_name' => 'ONCE',
                'official_colors' => json_encode(['#FE5F8F', '#FEE500']),
                'representative_symbol' => 'Candy Bong',
            ],
        );
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'talent',
            [
                'slug' => 'tl-chaeyoung',
                'language' => 'ko',
                'version' => 4,
                'theme_color' => '#FE5F8F',
                'sections' => json_encode([
                    [
                        'id' => 'overview',
                        'type' => 'plaintext',
                        'title' => 'Overview',
                        'content' => 'Published sample for checking the talent wiki state.',
                    ],
                ]),
            ],
            [
                'name' => '채영',
                'normalized_name' => 'chaeyoung',
                'real_name' => '손채영',
                'normalized_real_name' => 'sonchaeyoung',
                'birthday' => '1999-04-23',
                'representative_symbol' => 'Strawberry Princess',
                'position' => 'rapper',
                'mbti' => 'infp',
                'zodiac_sign' => 'taurus',
                'height' => 159,
                'blood_type' => 'B',
                'fandom_name' => 'ONCE',
                'group_identifiers' => json_encode(['01965bb2-bcc9-7c6f-8b90-89f7f217f002']),
            ],
        );

        $useCase = $this->app->make(GetTalentWikiInterface::class);
        $readModel = $useCase->process(new GetTalentWikiInput(new Slug('tl-chaeyoung'), Language::KOREAN));

        $this->assertInstanceOf(WikiReadModel::class, $readModel);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $readModel->wikiIdentifier());
        $this->assertSame('tl-chaeyoung', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('talent', $readModel->resourceType());
        $this->assertSame(4, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('채영', $readModel->basic()['name']);
        $this->assertSame('손채영', $readModel->basic()['realName']);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->basic()['groups'][0]['wikiIdentifier']);
        $this->assertSame('TWICE', $readModel->basic()['groups'][0]['name']);
        $this->assertSame('girl_group', $readModel->basic()['groups'][0]['groupType']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenTalentWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetTalentWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetTalentWikiInput(new Slug('tl-chaeyoung'), Language::KOREAN));
    }
}
