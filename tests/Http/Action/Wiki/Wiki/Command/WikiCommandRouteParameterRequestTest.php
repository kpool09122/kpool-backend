<?php

declare(strict_types=1);

namespace Tests\Http\Action\Wiki\Wiki\Command;

use Application\Http\Action\Wiki\Wiki\Command\ApproveWiki\ApproveWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\MergeWiki\MergeWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\PublishWiki\PublishWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\RejectWiki\RejectWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\RollbackWiki\RollbackWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\SubmitWiki\SubmitWikiRequest;
use Application\Http\Action\Wiki\Wiki\Command\TranslateWiki\TranslateWikiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiCommandRouteParameterRequestTest extends TestCase
{
    /**
     * @param class-string<FormRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    #[DataProvider('wikiCommandRequestProvider')]
    public function testUsesWikiIdRouteParameterForValidationAndAccessor(string $requestClass, array $payload): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $request = $this->createRequest($requestClass, $wikiId, $payload);

        $validator = Validator::make($request->validationData(), $request->rules());

        $this->assertTrue($validator->passes(), (string) $validator->errors());
        $this->assertSame($wikiId, $request->wikiId());
    }

    /**
     * @return iterable<string, array{class-string<FormRequest>, array<string, mixed>}>
     */
    public static function wikiCommandRequestProvider(): iterable
    {
        $approvalPayload = [
            'resourceType' => ResourceType::TALENT->value,
            'agencyIdentifier' => null,
            'groupIdentifiers' => [],
            'talentIdentifiers' => [],
        ];

        $editPayload = [
            ...$approvalPayload,
            'basic' => ['name' => 'Test Wiki'],
            'sections' => [],
            'themeColor' => null,
            'imageIdentifier' => null,
        ];

        yield 'approve' => [ApproveWikiRequest::class, $approvalPayload];
        yield 'merge' => [MergeWikiRequest::class, $editPayload];
        yield 'publish' => [
            PublishWikiRequest::class,
            $approvalPayload,
        ];
        yield 'reject' => [RejectWikiRequest::class, $approvalPayload];
        yield 'rollback' => [
            RollbackWikiRequest::class,
            [
                ...$approvalPayload,
                'targetVersion' => 1,
            ],
        ];
        yield 'submit' => [SubmitWikiRequest::class, $approvalPayload];
        yield 'translate' => [TranslateWikiRequest::class, $approvalPayload];
    }

    /**
     * @param class-string<FormRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    private function createRequest(string $requestClass, string $wikiId, array $payload): FormRequest
    {
        $request = $requestClass::create('/wiki/' . $wikiId . '/command', 'POST', $payload);
        $route = new Route(['POST'], '/wiki/{wikiId}/command', []);
        $route->bind($request);
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }
}
