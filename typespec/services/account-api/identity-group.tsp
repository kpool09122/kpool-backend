using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for creating an identity group.")
model CreateIdentityGroupRequestBody {
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Identity group name.")
  name: string;
  @doc("Role assigned to the identity group.")
  role: string;
}

@doc("Request body for mutating identity group membership.")
model MutateIdentityGroupMemberRequestBody {
  @doc("Identity identifier to add or remove.")
  identityIdentifier: Uuid;
}

@doc("Response returned when an identity group is created.")
model CreateIdentityGroupResponse {
  @statusCode statusCode: 201;
  @body body: CreatedIdentityGroupSummary;
}

@doc("Response returned when an identity group operation succeeds.")
model OkIdentityGroupResponse {
  @statusCode statusCode: 200;
  @body body: IdentityGroupSummary;
}

@route("/identity-groups")
interface IdentityGroupOperations {
  @post
  @doc("Create an identity group.")
  createIdentityGroup(
    @body body: CreateIdentityGroupRequestBody,
  ): CreateIdentityGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{identityGroupId}/add-member")
  @doc("Add an identity to an identity group.")
  addIdentityToIdentityGroup(
    @path identityGroupId: Uuid,
    @body body: MutateIdentityGroupMemberRequestBody,
  ): OkIdentityGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{identityGroupId}/remove-member")
  @doc("Remove an identity from an identity group.")
  removeIdentityFromIdentityGroup(
    @path identityGroupId: Uuid,
    @body body: MutateIdentityGroupMemberRequestBody,
  ): OkIdentityGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{identityGroupId}")
  @doc("Delete an identity group.")
  deleteIdentityGroup(
    @path identityGroupId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
