using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for requesting an account delegation.")
model RequestDelegationRequestBody {
  @doc("Account identifier of the affiliation counterpart.")
  targetAccountIdentifier: Uuid;
}

@doc("Account-level delegation request state.")
model AccountDelegationSummary {
  delegationIdentifier: Uuid;
  affiliationIdentifier: Uuid;
  delegateAccountIdentifier: Uuid;
  delegatorAccountIdentifier: Uuid;
  requestedByAccountIdentifier: Uuid;
  status: string;
  direction: string;
  requestedAt: Timestamp;
  approvedAt?: Timestamp | null;
  revokedAt?: Timestamp | null;
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
  @body body: AccountDelegationSummary;
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
  ): CreateDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

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
