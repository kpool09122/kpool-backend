<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\SubmitContact;

use Application\Mail\ContactReceivedMail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutput;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Factory\ContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Infrastructure\Service\ContactEmailService;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
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
        $emailService = Mockery::mock(ContactEmailServiceInterface::class);
        $this->app()->instance(ContactEmailServiceInterface::class, $emailService);
        $submitContact = $this->app()->make(SubmitContactInterface::class);
        $this->assertInstanceOf(SubmitContact::class, $submitContact);
    }

    /**
     * 正常系：正しくContact Entityが作成されてEmailサービスが実行されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[DataProvider('submitters')]
    public function testProcess(bool $signedIn): void
    {
        Mail::fake();
        $principalIdentifier = $signedIn ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null;
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
            $principalIdentifier,
            $category,
            $name,
            $email,
            $content,
            Language::JAPANESE,
        );

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $principalIdentifier,
            $category,
            $name,
            $email,
            $content,
            Language::JAPANESE,
        );
        $contactFactory = Mockery::mock(ContactFactoryInterface::class);
        $contactFactory->shouldReceive('create')
            ->once()
            ->with($category, $name, $email, $content, $principalIdentifier, Language::JAPANESE)
            ->andReturn($contact);

        $emailService = new ContactEmailService(new Email('admin@example.com'), new Email('support@example.com'));

        $contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $contactRepository->shouldReceive('save')
            ->once()
            ->with($contact)
            ->andReturnNull();

        $this->app()->instance(ContactFactoryInterface::class, $contactFactory);
        $this->app()->instance(ContactEmailServiceInterface::class, $emailService);
        $this->app()->instance(ContactRepositoryInterface::class, $contactRepository);
        $submitContact = $this->app()->make(SubmitContactInterface::class);
        $output = new SubmitContactOutput();
        $submitContact->process($input, $output);

        $this->assertSame([
            'contactIdentifier' => (string) $contactIdentifier,
            'principalIdentifier' => $principalIdentifier === null ? null : (string) $principalIdentifier,
            'category' => $category->value,
            'name' => (string) $name,
            'email' => (string) $email,
            'content' => (string) $content,
        ], $output->toArray());
        Mail::assertSentCount(1);
        Mail::assertSent(ContactReceivedMail::class, static fn (ContactReceivedMail $mail): bool => $mail->hasTo('admin@example.com') && $mail->contact === $contact);
    }

    /** @return array<string, array{bool}> */
    public static function submitters(): array
    {
        return ['signed in' => [true], 'anonymous' => [false]];
    }

    /**
     * 異常系：管理者へのメール送信に失敗した場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenFailedToSendEmailToAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
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
            $principalIdentifier,
            $category,
            $name,
            $email,
            $content,
            Language::JAPANESE,
        );

        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $principalIdentifier,
            $category,
            $name,
            $email,
            $content,
            Language::JAPANESE,
        );
        $contactFactory = Mockery::mock(ContactFactoryInterface::class);
        $contactFactory->shouldReceive('create')
            ->once()
            ->with($category, $name, $email, $content, $principalIdentifier, Language::JAPANESE)
            ->andReturn($contact);

        $emailService = Mockery::mock(ContactEmailServiceInterface::class);
        $emailService->shouldReceive('sendContactToAdministrator')
            ->once()
            ->with($contact)
            ->andThrow(new RuntimeException());

        $contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $contactRepository->shouldReceive('save')
            ->once()
            ->with($contact)
            ->andReturnNull();

        $this->app()->instance(ContactFactoryInterface::class, $contactFactory);
        $this->app()->instance(ContactEmailServiceInterface::class, $emailService);
        $this->app()->instance(ContactRepositoryInterface::class, $contactRepository);

        $this->expectException(FailedToSendEmailException::class);
        $submitContact = $this->app()->make(SubmitContactInterface::class);
        $submitContact->process($input, new SubmitContactOutput());
    }
}
