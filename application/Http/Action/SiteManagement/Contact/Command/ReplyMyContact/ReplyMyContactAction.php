<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Command\ReplyMyContact;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact\ReplyMyContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyMyContact\ReplyMyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ReplyMyContactAction
{
    public function __construct(
        private ReplyMyContactInterface $replyMyContact,
        private ActorContext $actorContext,
        private SiteManagementContext $siteManagementContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReplyMyContactRequest $request): Response
    {
        try {
            try {
                $input = new ReplyMyContactInput(
                    new ContactIdentifier($request->contactIdentifier()),
                    $this->siteManagementContext->principalIdentifier,
                    $request->content(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            // As with operator replies, do not roll back saved history on mail failure.
            $this->replyMyContact->process($input);
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (ContactNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('contact_not_found', $this->actorContext->language->value), previous: $e);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
