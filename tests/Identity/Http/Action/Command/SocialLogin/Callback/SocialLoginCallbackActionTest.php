<?php

declare(strict_types=1);

namespace Tests\Identity\Http\Action\Command\SocialLogin\Callback;

use Application\Http\Action\Identity\Command\SocialLogin\Callback\SocialLoginCallbackAction;
use Application\Http\Action\Identity\Command\SocialLogin\Callback\SocialLoginCallbackRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SocialLoginCallbackActionTest extends TestCase
{
    public function testInvokeReturnsRedirectUrlResponse(): void
    {
        $this->app['config']->set('app.frontend_url', 'http://localhost:3000');

        /** @var SocialLoginCallbackRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(SocialLoginCallbackRequest::class);
        $request->shouldReceive('provider')->andReturn('google');
        $request->shouldReceive('code')->andReturn('oauth-code');
        $request->shouldReceive('state')->andReturn('state-token');
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var SocialLoginCallbackInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(SocialLoginCallbackInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(function ($input): bool {
                    return $input instanceof SocialLoginCallbackInput
                        && (string) $input->state() === 'state-token';
                }),
                Mockery::on(function ($output): bool {
                    if (! $output instanceof SocialLoginCallbackOutput) {
                        return false;
                    }

                    $output->setRedirectUrl('/auth/callback');

                    return true;
                }),
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new SocialLoginCallbackAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('http://localhost:3000', $response->headers->get('Location'));
    }
}
