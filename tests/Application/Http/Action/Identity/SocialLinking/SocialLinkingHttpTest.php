<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\SocialLinking;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use DateTimeImmutable;
use Illuminate\Session\Middleware\StartSession;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSession;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInputPort;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailOutputPort;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInputPort;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailOutputPort;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;
use Throwable;

#[Group('useDb')]
class SocialLinkingHttpTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(StartSession::class)->group(dirname(__DIR__, 6) . '/routes/identity_api.php');
    }

    public function testGetPendingReturnsOnlyPublicMetadata(): void
    {
        $sessions = Mockery::mock(SocialLinkingSessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->andReturn(new SocialLinkingSession(
            new IdentityIdentifier('00000000-0000-7000-8000-000000000001'),
            new Email('owner@example.com'),
            new SocialConnection(SocialProvider::GOOGLE, 'secret-provider-user-id'),
            'https://allowed.example.com/profile',
            new DateTimeImmutable('2030-01-01T00:00:00+00:00'),
        ));
        $this->app()->instance(SocialLinkingSessionStorageServiceInterface::class, $sessions);

        $response = $this->getJson('/auth/social/link?identityIdentifier=attacker&returnTo=https://evil.example');

        $response->assertOk()->assertExactJson([
            'provider' => 'google',
            'email' => 'owner@example.com',
            'expiresAt' => '2030-01-01T00:00:00+00:00',
        ]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertGuest();
    }

    public function testSendUsesLanguageAndDoesNotAcceptClientSelectedTarget(): void
    {
        $useCase = Mockery::mock(SendSocialLinkingEmailInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(function (SendSocialLinkingEmailInputPort $input, SendSocialLinkingEmailOutputPort $output): void {
            $this->assertSame(Language::JAPANESE, $input->language());
            $output->setStatus(new EmailSendingStatus(true, 4, 60));
        });
        $this->app()->instance(SendSocialLinkingEmailInterface::class, $useCase);

        $response = $this->postJson('/auth/social/link/email', [
            'email' => 'attacker@example.com',
            'identityIdentifier' => 'attacker',
            'provider' => 'line',
            'providerUserId' => 'attacker',
            'returnTo' => 'https://evil.example',
        ], ['Accept-Language' => 'ja-JP']);

        $response->assertOk()->assertExactJson(['accepted' => true, 'remainingSends' => 4, 'retryAfterSeconds' => 60]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertGuest();
    }

    public function testVerifyUsesOnlyCodeAndReturnsServerChosenDestination(): void
    {
        $useCase = Mockery::mock(VerifySocialLinkingEmailInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(function (VerifySocialLinkingEmailInputPort $input, VerifySocialLinkingEmailOutputPort $output): void {
            $this->assertSame('012345', (string) $input->code());
            $output->setRedirectUrl('https://allowed.example.com/profile');
        });
        $this->app()->instance(VerifySocialLinkingEmailInterface::class, $useCase);

        $response = $this->postJson('/auth/social/link/email/verification', [
            'authCode' => '012345',
            'email' => 'attacker@example.com',
            'identityIdentifier' => 'attacker',
            'provider' => 'line',
            'providerUserId' => 'attacker',
            'returnTo' => 'https://evil.example',
            'purpose' => 'passkey_recovery',
        ]);

        $response->assertOk()->assertExactJson(['redirectUrl' => 'https://allowed.example.com/profile']);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    #[DataProvider('invalidCodes')]
    public function testVerificationRejectsMalformedCodeBeforeUseCase(mixed $code): void
    {
        $useCase = Mockery::mock(VerifySocialLinkingEmailInterface::class);
        $useCase->shouldNotReceive('process');
        $this->app()->instance(VerifySocialLinkingEmailInterface::class, $useCase);

        $this->postJson('/auth/social/link/email/verification', ['authCode' => $code])
            ->assertUnprocessable()->assertJsonValidationErrors('authCode');
        $this->assertGuest();
    }

    /** @return array<string, array{mixed}> */
    public static function invalidCodes(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'too short' => ['12345'],
            'too long' => ['1234567'],
            'letters' => ['abcdef'],
            'number' => [123456],
            'array' => [['123456']],
            'non ASCII' => ['１２３４５６'],
        ];
    }

    public function testVerificationRequiresCode(): void
    {
        $useCase = Mockery::mock(VerifySocialLinkingEmailInterface::class);
        $useCase->shouldNotReceive('process');
        $this->app()->instance(VerifySocialLinkingEmailInterface::class, $useCase);

        $this->postJson('/auth/social/link/email/verification')
            ->assertUnprocessable()->assertJsonValidationErrors('authCode');
    }

    #[DataProvider('domainFailures')]
    public function testDomainFailuresReturnGeneric422(string $method, string $url, string $interface, Throwable $exception): void
    {
        $useCase = Mockery::mock($interface);
        $useCase->shouldReceive($interface === SocialLinkingSessionStorageServiceInterface::class ? 'requireValid' : 'process')->once()->andThrow($exception);
        $this->app()->instance($interface, $useCase);

        $this->withHeader('Accept-Language', 'ja-JP')
            ->json($method, $url, ['authCode' => '123456'])
            ->assertUnprocessable()
            ->assertJsonPath('status', 422)
            ->assertJsonPath('detail', 'SSO連携リクエストが無効、期限切れ、または完了できません。');
        $this->assertGuest();
    }

    /** @return iterable<string, array{string, string, class-string, Throwable}> */
    public static function domainFailures(): iterable
    {
        foreach (self::operations() as $operation => [$method, $url, $interface]) {
            foreach ([
                'missing pending' => new SocialLinkingSessionInvalidException('Missing pending data'),
                'expired pending' => new SocialLinkingSessionInvalidException('Expired pending data'),
                'different session' => new SocialLinkingSessionInvalidException('Session mismatch'),
                'verification failed' => new SocialLinkingVerificationFailedException('Code mismatch'),
                'invalid input' => new InvalidArgumentException('Invalid input'),
            ] as $failure => $exception) {
                yield $operation . ': ' . $failure => [$method, $url, $interface, $exception];
            }
        }
    }

    #[DataProvider('operations')]
    public function testUnexpectedFailuresAreWrappedAsInternalServerErrors(string $method, string $url, string $interface): void
    {
        $this->withoutExceptionHandling();
        $useCase = Mockery::mock($interface);
        $useCase->shouldReceive($interface === SocialLinkingSessionStorageServiceInterface::class ? 'requireValid' : 'process')->once()->andThrow(new RuntimeException('Secret infrastructure details'));
        $this->app()->instance($interface, $useCase);

        $this->expectException(InternalServerErrorHttpException::class);
        $this->json($method, $url, ['authCode' => '123456']);
    }

    /** @return array<string, array{string, string, class-string}> */
    public static function operations(): array
    {
        return [
            'get pending' => ['GET', '/auth/social/link', SocialLinkingSessionStorageServiceInterface::class],
            'send email' => ['POST', '/auth/social/link/email', SendSocialLinkingEmailInterface::class],
            'verify email' => ['POST', '/auth/social/link/email/verification', VerifySocialLinkingEmailInterface::class],
        ];
    }
}
