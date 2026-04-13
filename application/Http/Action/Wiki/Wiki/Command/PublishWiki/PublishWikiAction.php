<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\PublishWiki;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\InconsistentVersionException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiOutput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class PublishWikiAction
{
    public function __construct(
        private PublishWikiInterface $publishWiki,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param PublishWikiRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(PublishWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new PublishWikiInput(
                    new DraftWikiIdentifier($request->wikiId()),
                    $request->publishedWikiIdentifier() !== null ? new WikiIdentifier($request->publishedWikiIdentifier()) : null,
                    $this->wikiContext->principalIdentifier,
                    ResourceType::from($request->resourceType()),
                    $request->agencyIdentifier() !== null ? new WikiIdentifier($request->agencyIdentifier()) : null,
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->groupIdentifiers()),
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->talentIdentifiers()),
                );
                $output = new PublishWikiOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->publishWiki->process($input, $output);
                DB::commit();
            } catch (WikiNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('wiki_not_found', $language), previous: $e);
            } catch (InvalidStatusException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('invalid_status', $language), previous: $e);
            } catch (InconsistentVersionException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('inconsistent_version', $language), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
