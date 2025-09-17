<?php

namespace Tests\SiteManagement\Contact\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\Email;
use Businesses\SiteManagement\Contact\Domain\Factory\ContactFactory;
use Businesses\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Category;
use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Businesses\SiteManagement\Contact\Domain\ValueObject\Content;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class ContactFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $contactFactory = $this->app->make(ContactFactoryInterface::class);
        $this->assertInstanceOf(ContactFactory::class, $contactFactory);
    }

    /**
     * 正常系: Contact Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
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
        $contactFactory = $this->app->make(ContactFactoryInterface::class);
        $contact = $contactFactory->create(
            $category,
            $name,
            $email,
            $content,
        );
        $this->assertTrue(UlidValidator::isValid((string)$contact->contactIdentifier()));
        $this->assertSame($category->value, $contact->category()->value);
        $this->assertSame((string)$name, (string)$contact->name());
        $this->assertSame((string)$email, (string)$contact->email());
        $this->assertSame((string)$content, (string)$contact->content());
    }
}
