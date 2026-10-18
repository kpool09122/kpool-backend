<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\ListDraftAnnouncements;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsOutput;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListDraftAnnouncementsAction
{
    public function __construct(
        private ListDraftAnnouncementsInterface $listDraftAnnouncements,
        private SiteManagementContext $siteManagementContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListDraftAnnouncementsRequest $request): JsonResponse
    {
        try {
            $output = new ListDraftAnnouncementsOutput();
            $this->listDraftAnnouncements->process(new ListDraftAnnouncementsInput(
                $this->siteManagementContext->principalIdentifier,
                $request->perPage(),
                $request->order(),
                $request->sort(),
                $request->searchWords(),
            ), $output);
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
