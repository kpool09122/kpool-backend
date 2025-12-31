<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $emailService = Mockery::mock(EmailServiceInterface::class);
        $this->app->instance(EmailServiceInterface::class, $emailService);
        $submitContact = $this->app->make(SubmitContactInterface::class);
        $this->assertInstanceOf(SubmitContact::class, $submitContact);
    }

    /**
     * 正常系：正しくContact Entityが作成されてEmailサービスが実行されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
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

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $category,
            $name,
            $email,
            $content,
        );
        $contactFactory = Mockery::mock(ContactFactoryInterface::class);
        $contactFactory->shouldReceive('create')
            ->once()
            ->with($category, $name, $email, $content)
            ->andReturn($contact);

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldReceive('sendContactToAdministrator')
            ->once()
            ->with($contact)
            ->andReturn(null);
        $emailService->shouldReceive('sendContactToUser')
            ->once()
            ->with($contact)
            ->andReturn(null);

        $this->app->instance(ContactFactoryInterface::class, $contactFactory);
        $this->app->instance(EmailServiceInterface::class, $emailService);
        $submitContact = $this->app->make(SubmitContactInterface::class);
        $submitContact->process($input);
    }

    /**
     * 異常系：管理者へのメール送信に失敗した場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenFailedToSendEmailToAdministrator(): void
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

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $category,
            $name,
            $email,
            $content,
        );
        $contactFactory = Mockery::mock(ContactFactoryInterface::class);
        $contactFactory->shouldReceive('create')
            ->once()
            ->with($category, $name, $email, $content)
            ->andReturn($contact);

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldReceive('sendContactToAdministrator')
            ->once()
            ->with($contact)
            ->andThrow(new RuntimeException());

        $this->app->instance(ContactFactoryInterface::class, $contactFactory);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $this->expectException(FailedToSendEmailException::class);
        $submitContact = $this->app->make(SubmitContactInterface::class);
        $submitContact->process($input);
    }

    /**
     * 異常系：問い合わせ者へのメール送信に失敗した場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenFailedToSendEmailToUser(): void
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

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $category,
            $name,
            $email,
            $content,
        );
        $contactFactory = Mockery::mock(ContactFactoryInterface::class);
        $contactFactory->shouldReceive('create')
            ->once()
            ->with($category, $name, $email, $content)
            ->andReturn($contact);

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldReceive('sendContactToAdministrator')
            ->once()
            ->with($contact)
            ->andReturn(null);
        $emailService->shouldReceive('sendContactToUser')
            ->once()
            ->with($contact)
            ->andThrow(new RuntimeException());

        $this->app->instance(ContactFactoryInterface::class, $contactFactory);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $this->expectException(FailedToSendEmailException::class);
        $submitContact = $this->app->make(SubmitContactInterface::class);
        $submitContact->process($input);
    }
}
