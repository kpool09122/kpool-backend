using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for requesting an affiliation.")
model RequestAffiliationRequestBody {
  @doc("Agency account identifier.")
  agencyAccountIdentifier: Uuid;
  @doc("Talent account identifier.")
  talentAccountIdentifier: Uuid;
  @doc("Account identifier that requested the affiliation.")
  requestedBy: Uuid;
  @doc("Optional contract terms.")
  terms?: AffiliationTermsSummary | null;
}

@doc("Request body for approving an affiliation.")
model ApproveAffiliationRequestBody {
  @doc("Approver account identifier.")
  approverAccountIdentifier: Uuid;
}

@doc("Request body for rejecting an affiliation.")
model RejectAffiliationRequestBody {
  @doc("Rejector account identifier.")
  rejectorAccountIdentifier: Uuid;
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

@route("/affiliations")
interface AffiliationOperations {
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
    @body body: ApproveAffiliationRequestBody,
  ): OkAffiliationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{affiliationId}/reject")
  @doc("Reject an affiliation.")
  rejectAffiliation(
    @path affiliationId: Uuid,
    @body body: RejectAffiliationRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{affiliationId}/terminate")
  @doc("Terminate an affiliation.")
  terminateAffiliation(
    @path affiliationId: Uuid,
    @body body: TerminateAffiliationRequestBody,
  ): OkAffiliationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
