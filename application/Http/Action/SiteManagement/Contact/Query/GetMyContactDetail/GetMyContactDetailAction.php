<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetMyContactDetailAction
{
    public function __construct(
        private GetMyContactDetailInterface $getMyContactDetail,
        private ActorContext $actorContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(GetMyContactDetailRequest $request): JsonResponse
    {
        try {
            $output = new GetMyContactDetailOutput();
            $this->getMyContactDetail->process(new GetMyContactDetailInput(
                $this->actorContext->identityIdentifier,
                new ContactIdentifier($request->contactIdentifier()),
            ), $output);
        } catch (ContactNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('contact_not_found', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
