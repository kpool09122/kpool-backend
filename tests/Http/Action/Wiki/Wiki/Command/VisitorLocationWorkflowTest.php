<?php

declare(strict_types=1);

namespace Tests\Http\Action\Wiki\Wiki\Command;

use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\RollbackWiki\RollbackWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\RollbackWiki\RollbackWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\WithdrawWiki\WithdrawWikiAction;
use Application\Http\Action\Wiki\Wiki\Command\WithdrawWiki\WithdrawWikiRequest;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Closure;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\ApproveWiki\ApproveWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiInterface;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Repository\WikiHistoryRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VisitorLocationWorkflowTest extends TestCase
{
    /** @return array<array{string, class-string, class-string<FormRequest>, class-string, string}> */
    public static function operations(): array
    {
        $operations = [
            ['SubmitWiki', SubmitWikiAction::class, SubmitWikiRequest::class, SubmitWikiInterface::class],
            ['ApproveWiki', ApproveWikiAction::class, ApproveWikiRequest::class, ApproveWikiInterface::class],
            ['RejectWiki', RejectWikiAction::class, RejectWikiRequest::class, RejectWikiInterface::class],
            ['WithdrawWiki', WithdrawWikiAction::class, WithdrawWikiRequest::class, WithdrawWikiInterface::class],
            ['PublishWiki', PublishWikiAction::class, PublishWikiRequest::class, PublishWikiInterface::class],
            ['RollbackWiki', RollbackWikiAction::class, RollbackWikiRequest::class, RollbackWikiInterface::class],
        ];
        $cases = [];
        foreach ($operations as $operation) {
            foreach (['signed', 'plain', 'stale', 'persistence failure', 'disallowed', 'invalid input'] as $mode) {
                $cases[] = [...$operation, $mode];
            }
        }

        return $cases;
    }

    /**
     * @param class-string $actionClass
     * @param class-string<FormRequest> $requestClass
     * @param class-string $useCaseClass
     */
    #[Group('useDb')]
    #[DataProvider('operations')]
    public function testActionPropagationAndTransaction(string $operation, string $actionClass, string $requestClass, string $useCaseClass, string $mode): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $actor = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $historyId = StrTestHelper::generateUuid();
        DB::table('wikis')->insert([
            'id' => $wikiId, 'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'visitor-location-' . $wikiId, 'language' => 'ja', 'resource_type' => 'group', 'version' => 1,
        ]);
        $this->app()->instance(WikiContext::class, new WikiContext($actor));
        $secret = 'test-only-location-workflow-secret-32-bytes';
        $this->app()['config']->set('wiki.visitor_location_secret', $secret);
        $path = '/api/v1/wiki/' . $wikiId . '/' . strtolower(str_replace('Wiki', '', $operation));
        $body = json_encode(['resourceType' => 'group', 'targetVersion' => 1, 'rejectionReason' => 'review'], JSON_THROW_ON_ERROR);
        $request = $requestClass::create($path, 'POST', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_COOKIE' => 'session=actor'], content: $body);
        self::assertInstanceOf(FormRequest::class, $request);
        $route = new Route('POST', '/api/v1/wiki/{wikiId}/operation', static fn () => null);
        $route->bind($request);
        $route->setParameter('wikiId', $mode === 'invalid input' ? 'invalid-id' : $wikiId);
        $request->setRouteResolver(static fn () => $route);
        $timestamp = (string) (time() - ($mode === 'stale' ? 301 : 0));
        $request->headers->set('X-Kpool-Visitor-Country', 'JP');
        $request->headers->set('X-Kpool-Visitor-Region', '01');
        $request->headers->set('X-Kpool-Visitor-Timestamp', $timestamp);
        if ($mode !== 'plain') {
            $payload = implode("\n", ['kpool-visitor-v1', $timestamp, 'POST', $path, hash('sha256', $body), hash('sha256', ''), hash('sha256', 'session=actor'), 'JP', '01']);
            $request->headers->set('X-Kpool-Visitor-Signature', hash_hmac('sha256', $payload, $secret));
        }
        $useCase = Mockery::mock($useCaseClass);
        if ($mode === 'invalid input') {
            $useCase->shouldNotReceive('process');
        } else {
            $useCase->shouldReceive('process')->once()->andReturnUsing(function (SubmitWikiInputPort|ApproveWikiInputPort|RejectWikiInputPort|WithdrawWikiInputPort|PublishWikiInputPort|RollbackWikiInputPort $input) use ($wikiId, $historyId, $actor, $mode): void {
                self::assertSame($actor, $input->principalIdentifier());
                $expectedCountry = in_array($mode, ['plain', 'stale'], true) ? null : 'JP';
                self::assertSame($expectedCountry, $input->visitorLocation()->country());
                self::assertSame($expectedCountry === null ? null : '01', $input->visitorLocation()->region());
                if ($mode === 'disallowed') {
                    throw new DisallowedException();
                }
                DB::table('wikis')->where('id', $wikiId)->update(['version' => 2]);
                (new WikiHistoryRepository())->save(new WikiHistory(
                    new WikiHistoryIdentifier($historyId),
                    HistoryActionType::Publish,
                    $actor,
                    null,
                    new WikiIdentifier($wikiId),
                    null,
                    null,
                    null,
                    null,
                    null,
                    new Name('fixture'),
                    new DateTimeImmutable(),
                    $input->visitorLocation(),
                ));
                if ($mode === 'persistence failure') {
                    // A database constraint failure after the Wiki update must roll back both writes.
                    DB::table('wiki_histories')->insert(['id' => $historyId]);
                }
            });
        }
        $this->app()->instance($useCaseClass, $useCase);
        $action = $this->app()->make($actionClass);
        self::assertTrue(is_callable($action));
        $invoke = Closure::fromCallable($action);
        $transactionLevel = DB::transactionLevel();

        try {
            $response = $invoke($request);
            self::assertInstanceOf(JsonResponse::class, $response);
            self::assertSame($mode === 'invalid input' ? 422 : ($mode === 'disallowed' ? 403 : 201), $response->getStatusCode());
            self::assertNotSame('persistence failure', $mode);
        } catch (InternalServerErrorHttpException) {
            self::assertSame('persistence failure', $mode);
        }
        self::assertSame($transactionLevel, DB::transactionLevel());
        $failed = in_array($mode, ['disallowed', 'persistence failure'], true) || $mode === 'invalid input';
        $this->assertDatabaseHas('wikis', ['id' => $wikiId, 'version' => $failed ? 1 : 2]);
        if ($failed) {
            $this->assertDatabaseMissing('wiki_histories', ['id' => $historyId]);
        } else {
            $this->assertDatabaseHas('wiki_histories', ['id' => $historyId, 'actor_id' => (string) $actor, 'visitor_country' => in_array($mode, ['plain', 'stale'], true) ? null : 'JP']);
        }
    }
}
