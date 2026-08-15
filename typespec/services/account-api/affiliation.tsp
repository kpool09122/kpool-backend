using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for requesting an affiliation.")
model RequestAffiliationRequestBody {
  @doc("Email address of the target account/principal that should receive the affiliation request.")
  targetEmail: string;
  @doc("Optional contract terms.")
  terms?: AffiliationTermsSummary | null;
}

@doc("Request body for terminating an affiliation.")
model TerminateAffiliationRequestBody {
  @doc("Terminator account identifier.")
  terminatorAccountIdentifier: Uuid;
}

@doc("Response returned when an affiliation is created.")
model CreateAffiliationResponse {
  @statusCode statusCode: 201;
  @body body: AffiliationSummary;
}

@doc("Response returned when an affiliation operation succeeds.")
model OkAffiliationResponse {
  @statusCode statusCode: 200;
  @body body: AffiliationSummary;
}

@doc("Response returned when listing affiliations succeeds.")
model ListAffiliationsResponse {
  @statusCode statusCode: 200;
  @body body: ListAffiliationsResponseBody;
}

@route("/affiliations")
interface AffiliationOperations {
  @get
  @doc("List affiliations related to the authenticated account.")
  listAffiliations(
    @query status?: string | null,
    @query viewerRole?: string | null,
    @query perPage?: int32 | null,
    @query page?: int32 | null,
  ): ListAffiliationsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @doc("Request an affiliation.")
  requestAffiliation(
    @body body: RequestAffiliationRequestBody,
  ): CreateAffiliationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{affiliationId}/approve")
  @doc("Approve an affiliation.")
  approveAffiliation(
    @path affiliationId: Uuid,
  ): OkAffiliationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{affiliationId}/reject")
  @doc("Reject an affiliation.")
  rejectAffiliation(
    @path affiliationId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{affiliationId}/terminate")
  @doc("Terminate an affiliation.")
  terminateAffiliation(
    @path affiliationId: Uuid,
    @body body: TerminateAffiliationRequestBody,
  ): OkAffiliationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
