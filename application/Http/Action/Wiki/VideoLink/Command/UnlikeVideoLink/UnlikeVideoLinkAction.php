<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\UnlikeVideoLink;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UnlikeVideoLinkAction
{
    public function __construct(
        private UnlikeVideoLinkInterface $unlikeVideoLink,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(UnlikeVideoLinkRequest $request): Response
    {
        try {
            try {
                $input = new UnlikeVideoLinkInput(new VideoLinkIdentifier($request->videoLinkId()), $this->wikiContext->principalIdentifier);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->unlikeVideoLink->process($input);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (VideoLinkNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('video_link_not_found', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
