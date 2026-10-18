<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\GetDraftAnnouncement;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetDraftAnnouncementAction
{
    public function __construct(
        private GetDraftAnnouncementInterface $getDraftAnnouncement,
        private SiteManagementContext $siteManagementContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(GetDraftAnnouncementRequest $request): JsonResponse
    {
        try {
            $result = $this->getDraftAnnouncement->process(new GetDraftAnnouncementInput(
                $this->siteManagementContext->principalIdentifier,
                new AnnouncementIdentifier($request->announcementIdentifier()),
            ));
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (AnnouncementNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('announcement_not_found', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($result->toArray(), Response::HTTP_OK);
    }
}
