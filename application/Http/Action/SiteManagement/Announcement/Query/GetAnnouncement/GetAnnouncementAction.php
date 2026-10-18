<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\GetAnnouncement;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetAnnouncementAction
{
    public function __construct(private GetAnnouncementInterface $getAnnouncement, private LoggerInterface $logger)
    {
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(GetAnnouncementRequest $request): JsonResponse
    {
        try {
            $result = $this->getAnnouncement->process(new GetAnnouncementInput(new AnnouncementIdentifier($request->announcementIdentifier()), Language::from($request->contentLanguage())));
        } catch (AnnouncementNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('published_announcement_not_found', $request->language()), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $request->language()), previous: $e);
        }

        return response()->json($result->toArray(), Response::HTTP_OK);
    }
}
