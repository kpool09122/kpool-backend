<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Tests\TestCase;

class SubmitContactInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $category = Category::SUGGESTIONS;
        $name = new ContactName('新機能の追加に関するお願い');
        $email = new Email('john.doe@example.com');
        $content = new Content('いつも楽しくサイトを利用させていただいております。

一つ、追加してほしい機能がありご連絡いたしました。
アーティストのプロフィールページに、公式のMV（ミュージックビデオ）一覧をYouTubeと連携して表示する機能は追加できないでしょうか？
新曲が出たときにすぐに見返せますし、新しいファンの方が過去の作品を知るきっかけにもなると思い、とても便利だと感じます。

ぜひ、ご検討いただけますと幸いです。
これからも応援しています。');
        $input = new SubmitContactInput(
            $category,
            $name,
            $email,
            $content,
        );
        $this->assertSame($category->value, $input->category()->value);
        $this->assertSame((string)$name, (string)$input->name());
        $this->assertSame((string)$email, (string)$input->email());
        $this->assertSame((string)$content, (string)$input->content());
    }
}
