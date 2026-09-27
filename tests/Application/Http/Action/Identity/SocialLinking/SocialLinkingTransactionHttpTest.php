<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\Identity\SocialLinking;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInputPort;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailOutputPort;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SocialLinkingTransactionHttpTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(StartSession::class)->group(dirname(__DIR__, 6) . '/routes/identity_api.php');
    }

    #[DataProvider('outcomes')]
    public function testActionCommitsOrRollsBackLinking(string $outcome, int $status): void
    {
        $id = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($id, ['email' => 'target@example.com']);
        CreateIdentity::createSocialConnection($id, SocialProvider::GOOGLE, 'existing-google');
        if ($outcome === 'conflict') {
            $otherId = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($otherId, ['email' => 'owner@example.com']);
            CreateIdentity::createSocialConnection($otherId, SocialProvider::GOOGLE, 'incoming-google');
        }
        $repository = $this->app()->make(IdentityRepositoryInterface::class);
        $transactionLevel = DB::transactionLevel();
        $useCase = Mockery::mock(VerifySocialLinkingEmailInterface::class);
        $useCase->shouldReceive('process')->once()->andReturnUsing(function (VerifySocialLinkingEmailInputPort $input, VerifySocialLinkingEmailOutputPort $output) use ($id, $repository, $outcome, $transactionLevel): void {
            $this->assertSame($transactionLevel + 1, DB::transactionLevel());
            $identity = $repository->findById($id);
            $this->assertNotNull($identity);
            $identity->addSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'incoming-google'));
            $repository->save($identity);
            if ($outcome === 'unexpected failure') {
                throw new RuntimeException('Failure after writing');
            }
            if ($outcome === 'domain failure') {
                throw new SocialLinkingVerificationFailedException();
            }
            $output->setRedirectUrl('/admin');
        });
        $this->app()->instance(VerifySocialLinkingEmailInterface::class, $useCase);

        $response = $this->postJson('/auth/social/link/email/verification', ['authCode' => '123456']);

        $response->assertStatus($status);
        $this->assertSame($transactionLevel, DB::transactionLevel());
        if ($outcome === 'success') {
            $response->assertExactJson(['redirectUrl' => '/admin']);
        }
        $fresh = $repository->findById($id);
        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->hasSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'existing-google')));
        $this->assertSame($outcome === 'success', $fresh->hasSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'incoming-google')));
        $this->assertCount($outcome === 'success' ? 2 : 1, $fresh->socialConnections());
        if ($outcome === 'conflict') {
            $owner = $repository->findBySocialConnection(SocialProvider::GOOGLE, 'incoming-google');
            $this->assertNotNull($owner);
            $this->assertNotSame((string) $id, (string) $owner->identityIdentifier());
        }
    }

    /** @return array<string, array{string, int}> */
    public static function outcomes(): array
    {
        return [
            'success' => ['success', 200],
            'unexpected failure' => ['unexpected failure', 500],
            'domain failure' => ['domain failure', 422],
            'conflict' => ['conflict', 422],
        ];
    }
}
