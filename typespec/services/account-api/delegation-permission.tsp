using TypeSpec.Http;

namespace KPool.AccountApi;

@doc("Request body for granting a delegation permission.")
model GrantDelegationPermissionRequestBody {
  @doc("Identity group identifier receiving the permission.")
  identityGroupIdentifier: Uuid;
  @doc("Account identifier that can be delegated.")
  targetAccountIdentifier: Uuid;
  @doc("Affiliation identifier covered by the permission.")
  affiliationIdentifier: Uuid;
}

@doc("Response returned when a delegation permission is created.")
model CreateDelegationPermissionResponse {
  @statusCode statusCode: 201;
  @body body: DelegationPermissionSummary;
}

@route("/delegation-permissions")
interface DelegationPermissionOperations {
  @post
  @doc("Grant a delegation permission.")
  grantDelegationPermission(
    @body body: GrantDelegationPermissionRequestBody,
  ): CreateDelegationPermissionResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{delegationPermissionId}")
  @doc("Revoke a delegation permission.")
  revokeDelegationPermission(
    @path delegationPermissionId: Uuid,
  ): NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
