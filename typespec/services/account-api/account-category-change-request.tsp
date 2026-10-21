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

@doc("Latest non-withdrawn request for the authenticated account, or null when none exists.")
model GetLatestAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: { request: AccountCategoryChangeRequestSummary | null; };
}

@doc("Updated request after withdrawal. Review fields and the current account category are preserved.")
model WithdrawAccountCategoryChangeRequestResponse {
  @statusCode statusCode: 200;
  @body body: AccountCategoryChangeRequestSummary;
}

interface AccountCategoryChangeRequestOperations {
  @get
  @route("/accounts/{accountIdentifier}/category-change-requests/latest")
  @doc("Get the authenticated account's latest non-withdrawn request, ordered by requestedAt descending and then identifier descending. No management permission is required.")
  getLatestAccountCategoryChangeRequest(
    @path accountIdentifier: Uuid,
  ): GetLatestAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/accounts/{accountIdentifier}/category-change-requests/{requestId}/withdraw")
  @doc("Withdraw a pending request belonging to the authenticated account. Terminal requests cannot be withdrawn. No management permission is required.")
  withdrawAccountCategoryChangeRequest(
    @path accountIdentifier: Uuid,
    @path requestId: Uuid,
  ): WithdrawAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/account-category-change-requests")
  @doc("List account category change requests for operations review.")
  listAccountCategoryChangeRequests(
    @query status?: "pending" | "approved" | "rejected" | "withdrawn" | null,
    @query requestedAccountCategory?: "agency" | "talent" | "general" | null,
    @query perPage?: int32 | null,
    @query page?: int32 | null,
  ): ListAccountCategoryChangeRequestsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/account-category-change-requests/{requestId}")
  @doc("Get an account category change request detail for operations review.")
  getAccountCategoryChangeRequest(
    @path requestId: Uuid,
  ): GetAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/account-category-change-requests/{requestId}/documents/{documentType}")
  @doc("View an independently stored application document snapshot. Requires category request management permission.")
  viewAccountCategoryChangeRequestDocument(
    @path requestId: Uuid,
    @path documentType: string,
  ): OkAccountDocumentFileResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/accounts/{accountIdentifier}/category-change-requests")
  @doc("Request account category change. Requires saved phone, address country, postal code, and address line 1, plus administrative area code where applicable. Accounts with any approved request history cannot apply again; rejected or withdrawn history permits a new request when the remaining requirements are met.")
  requestAccountCategoryChange(
    @path accountIdentifier: Uuid,
    @body body: RequestAccountCategoryChangeRequestBody,
  ): CreateAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/account-category-change-requests/{requestId}/approve")
  @doc("Approve an account category change request.")
  approveAccountCategoryChangeRequest(
    @path requestId: Uuid,
  ): ApproveAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/account-category-change-requests/{requestId}/reject")
  @doc("Reject an account category change request.")
  rejectAccountCategoryChangeRequest(
    @path requestId: Uuid,
    @body body: RejectAccountCategoryChangeRequestBody,
  ): RejectAccountCategoryChangeRequestResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
