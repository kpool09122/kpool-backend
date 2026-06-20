<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutput;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class SubmitContactAction
{
    public function __construct(
        private SubmitContactInterface $submitContact,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SubmitContactRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SubmitContactRequest $request): JsonResponse
    {
        $output = new SubmitContactOutput();

        try {
            try {
                $input = new SubmitContactInput(
                    null,
                    Category::from($request->category()),
                    new ContactName($request->name()),
                    new Email($request->email()),
                    new Content($request->content()),
                );
            } catch (InvalidArgumentException | ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->submitContact->process($input, $output);
                DB::commit();
            } catch (FailedToSendEmailException $e) {
                DB::rollBack();

                throw $e;
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string)$e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string)$e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        if ($output->contact() === null) {
            throw new InternalServerErrorHttpException();
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
