<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\MergeWiki;

use Application\Http\Action\Wiki\Wiki\Command\Support\WikiCommandPayloadMapper;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki\MergeWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki\MergeWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki\MergeWikiOutput;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiFontStyle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class MergeWikiAction
{
    public function __construct(
        private MergeWikiInterface $mergeWiki,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param MergeWikiRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(MergeWikiRequest $request): JsonResponse
    {
        try {
            try {
                $resourceType = ResourceType::from($request->resourceType());
                $basic = WikiCommandPayloadMapper::basic($resourceType, $request->basic());
                $sections = WikiCommandPayloadMapper::sections($request->sections());

                $input = new MergeWikiInput(
                    new DraftWikiIdentifier($request->wikiId()),
                    $basic,
                    $sections,
                    themeColor: $request->themeColor() !== null ? new Color($request->themeColor()) : null,
                    fontStyle: $request->fontStyle() !== null ? WikiFontStyle::from($request->fontStyle()) : null,
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    resourceType: $resourceType,
                    mergedAt: new DateTimeImmutable(),
                    agencyIdentifier: $request->agencyIdentifier() !== null ? new WikiIdentifier($request->agencyIdentifier()) : null,
                    groupIdentifiers: array_map(static fn (string $id) => new WikiIdentifier($id), $request->groupIdentifiers()),
                    talentIdentifiers: array_map(static fn (string $id) => new WikiIdentifier($id), $request->talentIdentifiers()),
                    imageIdentifier: $request->imageIdentifier() !== null ? new ImageIdentifier($request->imageIdentifier()) : null,
                    title: $request->title() !== null ? new SeoTitle($request->title()) : null,
                    metaDescription: $request->metaDescription() !== null ? new MetaDescription($request->metaDescription()) : null,
                    keywords: $request->keywords() !== null ? new SeoKeywords($request->keywords()) : null,
                );
                $output = new MergeWikiOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->mergeWiki->process($input, $output);
                DB::commit();
            } catch (WikiNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('wiki_not_found', $language), previous: $e);
            } catch (UnauthorizedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('unauthorized', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
