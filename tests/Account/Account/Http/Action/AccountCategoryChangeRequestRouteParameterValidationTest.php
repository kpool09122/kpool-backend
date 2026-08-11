<?php

declare(strict_types=1);

namespace Tests\Account\Account\Http\Action;

use Application\Http\Action\Account\Account\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestRequest;
use Application\Http\Action\Account\Account\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestRequest;
use Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountCategoryChangeRequestRouteParameterValidationTest extends TestCase
{
    /**
     * @param class-string<FormRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    #[DataProvider('validRequestProvider')]
    public function testRequestIdRouteParameterIsValidatedAsInput(string $requestClass, array $payload): void
    {
        $request = $this->request($requestClass, '01982342-f83c-7480-9c75-7362f88c2501', $payload);

        $request->validateResolved();

        $this->assertSame('01982342-f83c-7480-9c75-7362f88c2501', $request->input('requestId'));
    }

    /**
     * @param class-string<FormRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    #[DataProvider('validRequestProvider')]
    public function testInvalidRequestIdRouteParameterIsRejected(string $requestClass, array $payload): void
    {
        $request = $this->request($requestClass, 'not-a-uuid', $payload);

        $this->expectException(ValidationException::class);

        $request->validateResolved();
    }

    /**
     * @return iterable<string, array{class-string<FormRequest>, array<string, mixed>}>
     */
    public static function validRequestProvider(): iterable
    {
        yield 'get' => [GetAccountCategoryChangeRequestRequest::class, []];
        yield 'approve' => [ApproveAccountCategoryChangeRequestRequest::class, []];
        yield 'reject' => [RejectAccountCategoryChangeRequestRequest::class, [
            'rejectionReasonCode' => 'other',
            'rejectionReasonDetail' => '書類不足',
        ]];
    }

    /**
     * @param class-string<FormRequest> $requestClass
     * @param array<string, mixed> $payload
     */
    private function request(string $requestClass, string $requestId, array $payload): FormRequest
    {
        /** @var FormRequest $request */
        $request = $requestClass::create(
            "/api/account/account-category-change-requests/{$requestId}",
            'GET',
            $payload,
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(static function () use ($request, $requestId): Route {
            $route = new Route('GET', 'api/account/account-category-change-requests/{requestId}', []);
            $route->bind($request);
            $route->setParameter('requestId', $requestId);

            return $route;
        });

        return $request;
    }
}
