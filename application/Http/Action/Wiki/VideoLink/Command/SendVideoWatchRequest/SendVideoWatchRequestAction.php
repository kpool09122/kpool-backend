<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\SendVideoWatchRequest;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoAlreadyWatchedException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestDurationNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestLikeRequiredException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestResendNotAvailableException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestTargetNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestWeeklyLimitReachedException;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SendVideoWatchRequestAction
{
    public function __construct(
        private SendVideoWatchRequestInterface $sendVideoWatchRequest,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(SendVideoWatchRequestRequest $request): Response
    {
        try {
            try {
                $input = new SendVideoWatchRequestInput(new VideoLinkIdentifier($request->videoLinkId()), $this->wikiContext->principalIdentifier);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->sendVideoWatchRequest->process($input);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (VideoAlreadyWatchedException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_already_watched', $request->language()), previous: $e);
            } catch (VideoWatchRequestTargetNotEligibleException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_watch_request_target_not_eligible', $request->language()), previous: $e);
            } catch (VideoWatchRequestDurationNotEligibleException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_watch_request_duration_not_eligible', $request->language()), previous: $e);
            } catch (VideoWatchRequestLikeRequiredException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_watch_request_like_required', $request->language()), previous: $e);
            } catch (VideoWatchRequestResendNotAvailableException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_watch_request_resend_not_available', $request->language()), previous: $e);
            } catch (VideoWatchRequestWeeklyLimitReachedException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_watch_request_weekly_limit_reached', $request->language()), previous: $e);
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
        } catch (ConflictHttpException|ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
