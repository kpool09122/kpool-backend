<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\RejectWiki;

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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki\RejectWikiOutput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RejectWikiAction
{
    public function __construct(
        private RejectWikiInterface $rejectWiki,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RejectWikiRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RejectWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RejectWikiInput(
                    new DraftWikiIdentifier($request->wikiId()),
                    new PrincipalIdentifier($request->principalId()),
                    ResourceType::from($request->resourceType()),
                    $request->agencyIdentifier() !== null ? new WikiIdentifier($request->agencyIdentifier()) : null,
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->groupIdentifiers()),
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->talentIdentifiers()),
                );
                $output = new RejectWikiOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->rejectWiki->process($input, $output);
                DB::commit();
            } catch (WikiNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('wiki_not_found', $language), previous: $e);
            } catch (InvalidStatusException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('invalid_status', $language), previous: $e);
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
