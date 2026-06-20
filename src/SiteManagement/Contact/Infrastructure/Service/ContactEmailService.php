<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

readonly class ContactEmailService implements EmailServiceInterface
{
    public function __construct(
        private Email $administratorEmail,
    ) {
    }

    public function sendContactToUser(Contact $contact): void
    {
        Mail::raw(
            "お問い合わせありがとうございます。\n\n以下の内容でお問い合わせを受け付けました。\n\n"
            . (string) $contact->content(),
            function ($message) use ($contact): void {
                $message
                    ->to((string) $contact->email())
                    ->subject('お問い合わせを受け付けました');
            }
        );
    }

    public function sendContactToAdministrator(Contact $contact): void
    {
        Mail::raw(
            "お問い合わせが届きました。\n\n"
            . '名前: ' . (string) $contact->name() . "\n"
            . 'メールアドレス: ' . (string) $contact->email() . "\n"
            . 'カテゴリ: ' . $contact->category()->value . "\n\n"
            . (string) $contact->content(),
            function ($message): void {
                $message
                    ->to((string) $this->administratorEmail)
                    ->subject('お問い合わせが届きました');
            }
        );
    }

    public function sendReplyToUser(Email $toEmail, ReplyContent $content): void
    {
        Mail::raw(
            (string) $content,
            function ($message) use ($toEmail): void {
                $message
                    ->to((string) $toEmail)
                    ->subject('お問い合わせへの返信');
            }
        );
    }
}
