<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Query;

use Application\Models\Wiki\ImageDeletionRequest;
use Application\Models\Wiki\WikiImage;
use DateTimeInterface;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Image\Application\UseCase\Query\ImageDeletionRequestListItemReadModel;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInputPort;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListImageDeletionRequests\ListImageDeletionRequestsOutputPort;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

readonly class ListImageDeletionRequests implements ListImageDeletionRequestsInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListImageDeletionRequestsInputPort $input, ListImageDeletionRequestsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        /** @var list<ImageDeletionRequest> $requests */
        $requests = ImageDeletionRequest::query()
            ->with('image')
            ->whereNull('reviewed_at')
            ->whereHas('image')
            ->orderByDesc('requested_at')
            ->get()
            ->all();

        $readModels = [];
        foreach ($requests as $request) {
            $image = $request->image;
            if (! $image instanceof WikiImage) {
                continue;
            }

            $domainImage = $this->imageRepository->findById(new ImageIdentifier($image->id));
            if ($domainImage === null) {
                continue;
            }

            $resource = $this->imageAuthorizationResourceBuilder->buildFromImage($domainImage);
            if (
                ! $this->policyEvaluator->evaluate($principal, Action::APPROVE, $resource)
                && ! $this->policyEvaluator->evaluate($principal, Action::REJECT, $resource)
            ) {
                throw new DisallowedException();
            }

            $readModels[] = $this->toReadModel($image, $request);
        }

        $perPage = $input->perPage();
        $currentPage = max(1, (int) request()->query('page', 1));
        $total = count($readModels);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $pageItems = array_slice($readModels, ($currentPage - 1) * $perPage, $perPage);

        $output->output($pageItems, $currentPage, $lastPage, $total, $perPage);
    }

    private function toReadModel(WikiImage $image, ImageDeletionRequest $request): ImageDeletionRequestListItemReadModel
    {
        return new ImageDeletionRequestListItemReadModel(
            imageIdentifier: $image->id,
            url: ImageUrl::fromPath($image->image_path) ?? '',
            resourceType: $image->resource_type,
            translationSetIdentifier: $image->translation_set_identifier,
            displayOrder: $image->display_order,
            sourceUrl: $image->source_url,
            sourceName: $image->source_name,
            altText: $image->alt_text,
            isHidden: $image->is_hidden,
            uploadedAt: $this->formatDateTime($image->uploaded_at),
            name: $request->requester_name,
            email: $request->requester_email,
            reason: $request->reason,
        );
    }

    private function formatDateTime(mixed $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format(DateTimeInterface::ATOM);
        }

        return (string) $dateTime;
    }
}
