<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListRelatedProfiles;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class ListRelatedProfilesAction
{
    public function __construct(
        private ListRelatedProfilesInterface $listRelatedProfiles,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListRelatedProfilesRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListRelatedProfilesInput(
                    slug: new Slug($request->slug()),
                    language: Language::from($request->language()),
                    resourceType: ResourceType::from($request->resourceType()),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $output = new ListRelatedProfilesOutput();
                $this->listRelatedProfiles->process($input, $output);
            } catch (WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Wiki not found.', previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
