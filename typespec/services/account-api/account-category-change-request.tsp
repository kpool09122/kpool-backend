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

@doc("Response returned when an account category change request is approved.")
model ApproveAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: AccountCategoryChangeRequestSummary;
}

@doc("Request body for rejecting an account category change request.")
model RejectAccountCategoryChangeRequestBody {
  @doc("Rejection reason code.")
  rejectionReasonCode: "document_unclear" | "document_expired" | "document_mismatch" | "document_incomplete" | "fraudulent_document" | "other";

  @doc("Detailed rejection reason. Required when rejectionReasonCode is other.")
  rejectionReasonDetail?: string | null;
}

@doc("Response returned when an account category change request is rejected.")
model RejectAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: AccountCategoryChangeRequestSummary;
}

@doc("Response returned when getting an account category change request detail.")
model GetAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: AccountCategoryChangeRequestDetailResponseBody;
}

@doc("Response returned when listing account category change requests.")
model ListAccountCategoryChangeRequestsResponse {
  @statusCode statusCode: 200;
  @body body: ListAccountCategoryChangeRequestsResponseBody;
}

interface AccountCategoryChangeRequestOperations {
  @get
  @route("/account-category-change-requests")
  @doc("List account category change requests for operations review.")
  listAccountCategoryChangeRequests(
    @query status?: "pending" | "approved" | "rejected" | null,
    @query requestedAccountCategory?: "agency" | "talent" | "general" | null,
    @query perPage?: int32 | null,
    @query page?: int32 | null,
  ): ListAccountCategoryChangeRequestsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/account-category-change-requests/{requestId}")
  @doc("Get an account category change request detail for operations review.")
  getAccountCategoryChangeRequest(
    @path requestId: Uuid,
  ): GetAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/accounts/{accountIdentifier}/category-change-requests")
  @doc("Request account category change.")
  requestAccountCategoryChange(
    @path accountIdentifier: Uuid,
    @body body: RequestAccountCategoryChangeRequestBody,
  ): CreateAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/account-category-change-requests/{requestId}/approve")
  @doc("Approve an account category change request.")
  approveAccountCategoryChangeRequest(
    @path requestId: Uuid,
  ): ApproveAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/account-category-change-requests/{requestId}/reject")
  @doc("Reject an account category change request.")
  rejectAccountCategoryChangeRequest(
    @path requestId: Uuid,
    @body body: RejectAccountCategoryChangeRequestBody,
  ): RejectAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
