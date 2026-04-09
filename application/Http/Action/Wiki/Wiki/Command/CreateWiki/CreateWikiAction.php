<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\CreateWiki;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Application\Exception\DuplicateSlugException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki\CreateWikiOutput;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateWikiAction
{
    public function __construct(
        private CreateWikiInterface $createWiki,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateWikiRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateWikiRequest $request): JsonResponse
    {
        try {
            try {
                $resourceType = ResourceType::from($request->resourceType());
                $basicClass = BasicInterface::resolveClass($resourceType);
                $basic = $basicClass::fromArray($request->basic());
                $sections = SectionContentCollection::fromArray($request->sections());

                $input = new CreateWikiInput(
                    $request->publishedWikiIdentifier() !== null ? new WikiIdentifier($request->publishedWikiIdentifier()) : null,
                    Language::from($request->wikiLanguage()),
                    $resourceType,
                    $basic,
                    $sections,
                    $request->themeColor() !== null ? new Color($request->themeColor()) : null,
                    new Slug($request->slug()),
                    new PrincipalIdentifier($request->principalId()),
                    $request->agencyIdentifier() !== null ? new WikiIdentifier($request->agencyIdentifier()) : null,
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->groupIdentifiers()),
                    array_map(static fn (string $id) => new WikiIdentifier($id), $request->talentIdentifiers()),
                );
                $output = new CreateWikiOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->createWiki->process($input, $output);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (DuplicateSlugException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('duplicate_slug', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (ForbiddenHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
