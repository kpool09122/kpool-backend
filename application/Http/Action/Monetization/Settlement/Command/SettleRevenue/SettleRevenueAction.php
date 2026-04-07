<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Settlement\Command\SettleRevenue;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInput;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInterface;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueOutput;
use Source\Monetization\Settlement\Domain\Repository\SettlementScheduleRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SettleRevenueAction
{
    public function __construct(
        private SettleRevenueInterface $settleRevenue,
        private SettlementScheduleRepositoryInterface $settlementScheduleRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SettleRevenueRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SettleRevenueRequest $request): JsonResponse
    {
        try {
            try {
                $scheduleIdentifier = new SettlementScheduleIdentifier($request->settlementScheduleId());
                $gatewayFeeRate = new Percentage($request->gatewayFeeRate());
                $platformFeeRate = new Percentage($request->platformFeeRate());
                $periodStart = new DateTimeImmutable($request->periodStart());
                $periodEnd = new DateTimeImmutable($request->periodEnd());
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $language = $request->language();

            $schedule = $this->settlementScheduleRepository->findById($scheduleIdentifier);
            if ($schedule === null) {
                throw new NotFoundHttpException(detail: error_message('settlement_schedule_not_found', $language), previous: null);
            }

            $paidAmounts = array_map(
                fn (array $item) => new Money((int) $item['amount'], Currency::from($item['currency'])),
                $request->paidAmounts()
            );

            $fixedFee = $request->fixedFeeAmount() !== null
                ? new Money($request->fixedFeeAmount(), Currency::from((string) $request->fixedFeeCurrency()))
                : null;

            $input = new SettleRevenueInput(
                $schedule,
                $paidAmounts,
                $gatewayFeeRate,
                $platformFeeRate,
                $fixedFee,
                $periodStart,
                $periodEnd,
            );
            $output = new SettleRevenueOutput();

            DB::beginTransaction();

            try {
                $this->settleRevenue->process($input, $output);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
