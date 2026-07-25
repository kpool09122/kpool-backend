<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyContactInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成され、値を取得できること
     */
    public function testConstruct(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $content = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';

        $input = new ReplyContactInput(
            $contactIdentifier,
            $identityIdentifier,
            $content,
        );

        $this->assertSame((string)$contactIdentifier, (string)$input->contactIdentifier());
        $this->assertSame((string)$identityIdentifier, (string)$input->identityIdentifier());
        $this->assertSame($content, $input->content());
    }
}
