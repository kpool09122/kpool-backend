<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Command\DeleteAnnouncement;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeleteAnnouncementAction
{
    public function __construct(
        private DeleteAnnouncementInterface $deleteAnnouncement,
        private SiteManagementContext $siteManagementContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeleteAnnouncementRequest $request): JsonResponse
    {
        try {
            $input = new DeleteAnnouncementInput(
                $this->siteManagementContext->principalIdentifier,
                new AnnouncementIdentifier($request->announcementIdentifier()),
            );
            $output = new DeleteAnnouncementOutput();
            DB::transaction(fn () => $this->deleteAnnouncement->process($input, $output));
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
