<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Jobs\SendAccountAuthCodeJob;
use Mockery;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendAccountAuthCodeJobTest extends TestCase
{
    public function testHandleCallsSendAuthCodeUseCase(): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $job = new SendAccountAuthCodeJob($email, $language);

        /** @var SendAuthCodeInterface&\Mockery\MockInterface $sendAuthCode */
        $sendAuthCode = Mockery::mock(SendAuthCodeInterface::class);
        $sendAuthCode->shouldReceive('process')
            ->once()
            ->with(Mockery::on(
                static fn (SendAuthCodeInput $input): bool => (string) $input->email() === (string) $email
                    && $input->language() === $language
            ));

        $job->handle($sendAuthCode);
    }
}
