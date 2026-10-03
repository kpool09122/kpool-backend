<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\EmailSending;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInputPort;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeOutputPort;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInputPort;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailInterface;
use Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail\SendPasskeyRecoveryEmailOutputPort;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

#[Group('useDb')]
class EmailSendingHttpTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware([])->group(dirname(__DIR__, 6) . '/routes/identity_api.php');
    }

    /** @param class-string $interface */
    #[DataProvider('endpointProvider')]
    public function testEmailSendingEndpointsReturnTheCommonStatus(string $uri, string $interface): void
    {
        $useCase = Mockery::mock($interface);
        $useCase->shouldReceive('process')->once()->andReturnUsing(function (SendAuthCodeInputPort|SendPasskeyRecoveryEmailInputPort $input, SendAuthCodeOutputPort|SendPasskeyRecoveryEmailOutputPort $output): void {
            $this->assertInstanceOf(Language::class, $input->language());
            $this->assertSame('user@example.com', (string) $input->email());
            $output->setStatus(new EmailSendingStatus(false, 3, 42));
        });
        $this->app()->instance($interface, $useCase);

        $response = $this->postJson($uri, ['email' => 'user@example.com'], ['Accept-Language' => 'ja-JP']);

        $response->assertOk()->assertExactJson([
            'accepted' => true,
            'remainingSends' => 3,
            'retryAfterSeconds' => 42,
        ]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /** @return array<string, array{string, class-string}> */
    public static function endpointProvider(): array
    {
        return [
            'auth code' => ['/auth/send-auth-code', SendAuthCodeInterface::class],
            'passkey recovery' => ['/auth/passkeys/recovery/email', SendPasskeyRecoveryEmailInterface::class],
        ];
    }
}
