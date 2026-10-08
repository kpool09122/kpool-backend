<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Command\ReplyContact;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ReplyContactAction
{
    public function __construct(
        private ReplyContactInterface $replyContact,
        private ActorContext $actorContext,
        private SiteManagementContext $siteManagementContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(ReplyContactRequest $request): Response
    {
        try {
            // Do not wrap this use case in a transaction: mail failures must retain the failed reply history.
            $this->replyContact->process(
                new ReplyContactInput(
                    new ContactIdentifier($request->contactIdentifier()),
                    $this->siteManagementContext->principalIdentifier,
                    $request->content(),
                ),
            );
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (ContactNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('contact_not_found', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
