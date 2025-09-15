<?php

namespace Tests\Wiki\Song\Domain\ValueObject;

use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class OverviewTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $text = '"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.';
        $overView = new Overview($text);
        $this->assertSame($text, (string)$overView);
    }

    /**
     * 異常系：空文字が渡された場合、空文字が返却されること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $text = '';
        $overView = new Overview($text);
        $this->assertSame($text, (string)$overView);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Overview(StrTestHelper::generateStr(Overview::MAX_LENGTH + 1));
    }
}
