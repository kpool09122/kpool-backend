<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePolicy;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyOutput;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CreatePolicyAction
{
    public function __construct(
        private CreatePolicyInterface $createPolicy,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreatePolicyRequest $request): JsonResponse
    {
        try {
            try {
                $statements = array_map(
                    static fn (array $s) => new Statement(
                        Effect::from($s['effect']),
                        array_map(static fn (string $a) => Action::from($a), $s['actions']),
                        array_map(static fn (string $r) => ResourceType::from($r), $s['resourceTypes']),
                        isset($s['condition']) ? new Condition(
                            array_map(
                                static fn (array $c) => new ConditionClause($c['field'], $c['operator'], $c['value']),
                                $s['condition']['clauses'] ?? [],
                            ),
                        ) : null,
                    ),
                    $request->statements(),
                );

                $input = new CreatePolicyInput(
                    $request->name(),
                    $statements,
                    $request->isSystemPolicy(),
                );
                $output = new CreatePolicyOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createPolicy->process($input, $output);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
