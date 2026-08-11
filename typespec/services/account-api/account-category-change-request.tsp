using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for creating an account category change request.")
model RequestAccountCategoryChangeRequestBody {
  @doc("Requested account category.")
  requestedAccountCategory: "agency" | "talent" | "general";
}

@doc("Response returned when an account category change request is created.")
model CreateAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 201;
  @body body: AccountCategoryChangeRequestSummary;
}

@route("/accounts/{accountIdentifier}/category-change-requests")
interface AccountCategoryChangeRequestOperations {
  @post
  @doc("Request account category change.")
  requestAccountCategoryChange(
    @path accountIdentifier: Uuid,
    @body body: RequestAccountCategoryChangeRequestBody,
  ): CreateAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
