<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service;

use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SesMailConfigurationTest extends TestCase
{
    public function testSesMailerCanBeResolvedWithoutStaticAwsCredentials(): void
    {
        config(['mail' => require __DIR__ . '/../../../../config/mail.php']);
        config(['services' => require __DIR__ . '/../../../../config/services.php']);

        self::assertInstanceOf(SesTransport::class, Mail::mailer('ses')->getSymfonyTransport());
    }
}
