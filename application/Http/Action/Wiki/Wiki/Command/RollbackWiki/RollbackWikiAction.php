<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\RollbackWiki;

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
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiOutput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RollbackWikiAction
{
    public function __construct(
        private RollbackWikiInterface $rollbackWiki,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RollbackWikiRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RollbackWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RollbackWikiInput(
                    $this->wikiContext->principalIdentifier,
                    new WikiIdentifier($request->wikiId()),
                    new Version($request->targetVersion()),
                    ResourceType::from($request->resourceType()),
                    $request->agencyIdentifier() !== null ? new WikiIdentifier($request->agencyIdentifier()) : null,
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->groupIdentifiers()),
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->talentIdentifiers()),
                );
                $output = new RollbackWikiOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->rollbackWiki->process($input, $output);
                DB::commit();
            } catch (WikiNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('wiki_not_found', $language), previous: $e);
            } catch (SnapshotNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('snapshot_not_found', $language), previous: $e);
            } catch (VersionMismatchException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('version_mismatch', $language), previous: $e);
            } catch (InvalidRollbackTargetVersionException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_rollback_target_version', $language), previous: $e);
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
