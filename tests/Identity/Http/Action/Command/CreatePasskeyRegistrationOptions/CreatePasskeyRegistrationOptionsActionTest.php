<?php

declare(strict_types=1);

namespace Tests\Identity\Http\Action\Command\CreatePasskeyRegistrationOptions;

use Illuminate\Support\Facades\Route;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInputPort;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsOutputPort;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Tests\TestCase;

class CreatePasskeyRegistrationOptionsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('session.driver', 'array');
        $this->app['router']->aliasMiddleware('session', \Illuminate\Session\Middleware\StartSession::class);
        Route::middleware(['api', 'session'])
            ->prefix('api/identity')
            ->group(__DIR__ . '/../../../../../../routes/identity_api.php');
    }

    public function testItReturnsRegistrationOptionsFromThePublicRoute(): void
    {
        /** @var MockInterface&CreatePasskeyRegistrationOptionsInterface $useCase */
        $useCase = Mockery::mock(CreatePasskeyRegistrationOptionsInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static fn (CreatePasskeyRegistrationOptionsInputPort $input): bool =>
                    (string) $input->email() === 'passkey@example.com'
                    && $input->signupSession()->accountType()?->value === 'individual'
                    && $input->signupSession()->returnTo() === '/welcome'),
                Mockery::on(static function (CreatePasskeyRegistrationOptionsOutputPort $output): bool {
                    $output->setOptions(
                        new ChallengeSessionKey('01994e3a-a15e-72d3-a456-426614174000'),
                        new WebAuthnOptions('{"challenge":"challenge"}'),
                    );

                    return true;
                }),
            );
        $this->app->instance(CreatePasskeyRegistrationOptionsInterface::class, $useCase);

        $response = $this->postJson('/api/identity/auth/passkeys/registration/options', [
            'email' => 'passkey@example.com',
            'accountType' => 'individual',
            'return_to' => '/welcome',
        ]);

        $response->assertOk()->assertExactJson([
            'challengeKey' => '01994e3a-a15e-72d3-a456-426614174000',
            'options' => ['challenge' => 'challenge'],
        ]);
    }

    public function testItRejectsAnExistingIdentityWithProblemDetails(): void
    {
        /** @var MockInterface&CreatePasskeyRegistrationOptionsInterface $useCase */
        $useCase = Mockery::mock(CreatePasskeyRegistrationOptionsInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new AlreadyUserExistsException());
        $this->app->instance(CreatePasskeyRegistrationOptionsInterface::class, $useCase);

        $response = $this->postJson('/api/identity/auth/passkeys/registration/options', [
            'email' => 'existing@example.com',
        ]);

        $response->assertConflict();
        $this->assertSame(409, $response->json('status'));
    }

    public function testItValidatesTheRequestBeforeCallingTheUseCase(): void
    {
        /** @var MockInterface&CreatePasskeyRegistrationOptionsInterface $useCase */
        $useCase = Mockery::mock(CreatePasskeyRegistrationOptionsInterface::class);
        $useCase->shouldNotReceive('process');
        $this->app->instance(CreatePasskeyRegistrationOptionsInterface::class, $useCase);

        $this->postJson('/api/identity/auth/passkeys/registration/options', [
            'email' => 'not-an-email',
            'accountType' => 'unknown',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'accountType']);
    }
}
