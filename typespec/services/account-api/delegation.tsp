using TypeSpec.Http;

namespace KPool.AccountApi;

@doc("Request body for requesting a delegation.")
model RequestDelegationRequestBody {
  @doc("Affiliation identifier used for delegation.")
  affiliationIdentifier: Uuid;
  @doc("Identity identifier of the delegate.")
  delegateIdentifier: Uuid;
  @doc("Identity identifier of the delegator.")
  delegatorIdentifier: Uuid;
}

@doc("Request body for approving a delegation.")
model ApproveDelegationRequestBody {
  @doc("Identity identifier approving the delegation.")
  approverIdentifier: Uuid;
}

@doc("Request body for revoking a delegation.")
model RevokeDelegationRequestBody {
  @doc("Identity identifier revoking the delegation.")
  revokerIdentifier: Uuid;
}

@doc("Response returned when a delegation is created.")
model CreateDelegationResponse {
  @statusCode statusCode: 201;
  @body body: DelegationSummary;
}

@doc("Response returned when a delegation operation succeeds.")
model OkDelegationResponse {
  @statusCode statusCode: 200;
  @body body: DelegationSummary;
}

@route("/delegations")
interface DelegationOperations {
  @post
  @doc("Request a delegation.")
  requestDelegation(
    @body body: RequestDelegationRequestBody,
  ): CreateDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{delegationId}/approve")
  @doc("Approve a delegation.")
  approveDelegation(
    @path delegationId: Uuid,
    @body body: ApproveDelegationRequestBody,
  ): OkDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{delegationId}/revoke")
  @doc("Revoke a delegation.")
  revokeDelegation(
    @path delegationId: Uuid,
    @body body: RevokeDelegationRequestBody,
  ): OkDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
