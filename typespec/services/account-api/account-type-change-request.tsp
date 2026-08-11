using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for creating an account type change request.")
model RequestAccountTypeChangeRequestBody {
  @doc("Target account identifier.")
  accountIdentifier: Uuid;
  @doc("Requested account type.")
  requestedAccountType: string;
}

@doc("Response returned when an account type change request is created.")
model CreateAccountTypeChangeRequestResponse {
  @statusCode statusCode: 201;
  @body body: AccountTypeChangeRequestSummary;
}

@doc("Response returned when an account type change request is approved.")
model ApproveAccountTypeChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: AccountTypeChangeRequestSummary;
}

@route("/account-type-change-requests")
interface AccountTypeChangeRequestOperations {
  @post
  @doc("Request account type change.")
  requestAccountTypeChange(
    @body body: RequestAccountTypeChangeRequestBody,
  ): CreateAccountTypeChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{requestId}/approve")
  @doc("Approve account type change request.")
  approveAccountTypeChangeRequest(
    @path requestId: Uuid,
  ): ApproveAccountTypeChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
