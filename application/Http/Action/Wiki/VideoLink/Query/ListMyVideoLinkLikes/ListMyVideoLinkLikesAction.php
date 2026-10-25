<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListMyVideoLinkLikes;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Throwable;

readonly class ListMyVideoLinkLikesAction
{
    public function __construct(
        private ListMyVideoLinkLikesInterface $listMyVideoLinkLikes,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListMyVideoLinkLikesRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListMyVideoLinkLikesInput(
                    $this->wikiContext->principalIdentifier,
                    array_map(static fn (string $id): VideoLinkIdentifier => new VideoLinkIdentifier($id), $request->videoLinkIds()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            }
            $output = new ListMyVideoLinkLikesOutput();
            $this->listMyVideoLinkLikes->process($input, $output);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray());
    }
}
