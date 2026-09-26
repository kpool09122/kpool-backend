<?php

declare(strict_types=1);

namespace Tests\Http\Action\Identity\Command\RecoverPasskey;

use Application\Http\Action\Identity\Command\RecoverPasskey\RecoverPasskeyAction;
use Application\Http\Action\Identity\Command\RecoverPasskey\RecoverPasskeyRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\NullLogger;
use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskeyInterface;
use Source\Identity\Domain\Exception\ChallengeSessionIdentityMismatchException;
use Tests\TestCase;

class RecoverPasskeyActionTest extends TestCase
{
    public function testChallengeMismatchReturns422AndRollsBack(): void
    {
        /** @var RecoverPasskeyInterface&\Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RecoverPasskeyInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new ChallengeSessionIdentityMismatchException());
        DB::shouldReceive('beginTransaction')->once()->ordered();
        DB::shouldReceive('rollBack')->once()->ordered();
        DB::shouldReceive('commit')->never();
        $request = RecoverPasskeyRequest::create('/', 'POST', [
            'recoveryKey' => '123e4567-e89b-72d3-a456-426614174001',
            'challengeKey' => '123e4567-e89b-72d3-a456-426614174002',
            'displayName' => 'recovered',
            'credential' => ['id' => 'credential'],
        ]);

        $response = (new RecoverPasskeyAction($useCase, new NullLogger()))($request);

        $this->assertSame(422, $response->getStatusCode());
    }
}
