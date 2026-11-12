<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Wiki\Domain\Exception\NgWordDetectedException;
use Source\Wiki\Wiki\Domain\Service\WikiNgWordValidationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Tests\TestCase;

class WikiNgWordValidationServiceTest extends TestCase
{
    #[DataProvider('prohibitedTexts')]
    public function testRejectsProhibitedText(string $text): void
    {
        $this->expectException(NgWordDetectedException::class);
        $this->validateText($text);
    }

    public function testAcceptsTermsEmbeddedInOtherWords(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validateText('Scunthorpe のイベントで Pornthip がイブラヒモビッチと共演した。「デビューまで死ねない」と話した。');
    }

    public function testAcceptsOrdinaryWordsUsedInTheNewPhrases(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validateText('The concert video leaked. 한국어 공부를 시작했다. 她喜欢拍照。 La cantante habló de salud sexual.');
    }

    private function validateText(string $text): void
    {
        $basic = new GroupBasic(
            name: new Name('TWICE'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
        );
        $sections = new SectionContentCollection([
            new Section('Profile', 0, new SectionContentCollection([new TextBlock(0, $text)])),
        ]);

        new WikiNgWordValidationService()->validate($basic, $sections, null, null, null);
    }

    /** @return iterable<string, array{string}> */
    public static function prohibitedTexts(): iterable
    {
        yield 'Japanese phrase in a sentence' => ['あいつは人間のクズだ。'];
        yield 'Japanese standalone insult with punctuation' => ['「死ね！」と書いた。'];
        yield 'English explicit content' => ['Posts advertising ＰＯＲＮ.'];
        yield 'English plural insult' => ['Those assholes!'];
        yield 'Korean insult' => ['저 개새끼들'];
        yield 'Simplified Chinese insult' => ['你给我去死吧'];
        yield 'Traditional Chinese insult' => ['你給我去死吧'];
        yield 'Spanish phrase with repeated whitespace' => ["¡VETE A\tLA  MIERDA!"];
        yield 'Japanese appearance-based insult' => ['あの人はブサイクすぎる。'];
        yield 'Japanese demand for intimate images' => ['裸の写真を送れって言った。'];
        yield 'English demand for intimate images' => ['SEND ME NUDES!'];
        yield 'Korean appearance-based insult' => ['쟤 존나 못생겼다'];
        yield 'Simplified Chinese demand for intimate images' => ['快发裸照给我'];
        yield 'Traditional Chinese demand for intimate images' => ['快發裸照給我'];
        yield 'Spanish harassment' => ['¡Ojalá te mueras!'];
        yield 'English appearance-based insult' => ['You ugly freak!'];
        yield 'English leaked intimate footage' => ['A post sharing LEAKED SEX VIDEOS.'];
        yield 'Korean dehumanizing insult' => ['저 사람은 인간 말종이야.'];
        yield 'Korean leaked intimate footage' => ['유출된 성관계 영상이 올라왔다.'];
        yield 'Korean demand for intimate images' => ['알몸 사진 보내 줘.'];
        yield 'Simplified Chinese appearance-based insult' => ['你长得像头猪。'];
        yield 'Traditional Chinese appearance-based insult' => ['你長得像頭豬。'];
        yield 'Simplified Chinese explicit footage' => ['有人发布了偷拍性爱视频。'];
        yield 'Traditional Chinese explicit footage' => ['有人發布了偷拍性愛影片。'];
        yield 'Spanish appearance-based insult' => ['¡QUÉ FEA ERES!'];
        yield 'Spanish explicit content' => ['Publicó un vídeo íntimo filtrado.'];
        yield 'Spanish unaccented request for intimate images' => ['Enviame fotos sin ropa.'];
    }
}
