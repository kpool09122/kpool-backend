<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Command\PublishAnnouncement;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementOutput;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class PublishAnnouncementAction
{
    public function __construct(
        private PublishAnnouncementInterface $publishAnnouncement,
        private SiteManagementContext $siteManagementContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function __invoke(PublishAnnouncementRequest $request): JsonResponse
    {
        try {
            $input = new PublishAnnouncementInput(
                $this->siteManagementContext->principalIdentifier,
                new AnnouncementIdentifier($request->announcementIdentifier()),
            );
            $output = new PublishAnnouncementOutput();
            DB::transaction(fn () => $this->publishAnnouncement->process($input, $output));
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (AnnouncementNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('announcement_not_found', $this->actorContext->language->value), previous: $e);
        } catch (InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
