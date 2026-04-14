using TypeSpec.Http;

namespace KPool.AccountApi;

@doc("Request body for creating an account.")
model CreateAccountRequestBody {
  @doc("Account email address.")
  email: string;
  @doc("Account type.")
  accountType: string;
  @doc("Account display name.")
  accountName: string;
  @doc("Identity identifier to attach to the account.")
  identityIdentifier?: Uuid | null;
}

@doc("Response returned when an account is created.")
model CreateAccountResponse {
  @statusCode statusCode: 201;
  @body body: CreatedAccountSummary;
}

@doc("Response returned when an account operation succeeds.")
model OkAccountResponse {
  @statusCode statusCode: 200;
  @body body: AccountSummary;
}

@route("/accounts")
interface AccountOperations {
  @post
  @doc("Create an account.")
  createAccount(
    @body body: CreateAccountRequestBody,
  ): CreateAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{accountId}")
  @doc("Delete an account.")
  deleteAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
