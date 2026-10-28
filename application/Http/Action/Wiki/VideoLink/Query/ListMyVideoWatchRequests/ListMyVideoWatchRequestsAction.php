<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListMyVideoWatchRequests;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoWatchRequestStatusReadModel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyVideoWatchRequestsAction
{
    public function __construct(
        private ListMyVideoWatchRequestsInterface $listMyVideoWatchRequests,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListMyVideoWatchRequestsRequest $request): Response
    {
        try {
            try {
                $input = new ListMyVideoWatchRequestsInput(array_map(static fn (string $id): VideoLinkIdentifier => new VideoLinkIdentifier($id), $request->videoLinkIds()), $this->wikiContext->principalIdentifier);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            }

            try {
                $output = $this->listMyVideoWatchRequests->process($input);
            } catch (DisallowedException $e) {

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (VideoLinkNotFoundException $e) {

                throw new NotFoundHttpException(detail: error_message('video_link_not_found', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json(['videoWatchRequests' => array_map(static fn (VideoWatchRequestStatusReadModel $state): array => $state->toArray(), $output)], Response::HTTP_OK);
    }
}
