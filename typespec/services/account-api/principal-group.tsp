using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for creating an principal group.")
model CreatePrincipalGroupRequestBody {
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
}

@doc("Request body for mutating principal group membership.")
model MutatePrincipalGroupMemberRequestBody {
  @doc("Identity identifier to add or remove.")
  principalIdentifier: Uuid;
}

@doc("Response returned when an principal group is created.")
model CreatePrincipalGroupResponse {
  @statusCode statusCode: 201;
  @body body: CreatedPrincipalGroupSummary;
}

@doc("Response returned when an principal group operation succeeds.")
model OkPrincipalGroupResponse {
  @statusCode statusCode: 200;
  @body body: PrincipalGroupSummary;
}

@doc("Response returned when account members are listed.")
model ListMembersResponse {
  @statusCode statusCode: 200;
  @body body: ListMembersResponseBody;
}

@doc("Response returned when principal groups are listed.")
model ListPrincipalGroupsResponse {
  @statusCode statusCode: 200;
  @body body: ListPrincipalGroupsResponseBody;
}

@route("/members")
interface MemberOperations {
  @get
  @doc("List account members.")
  listMembers(): ListMembersResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/principal-groups")
interface PrincipalGroupOperations {
  @get
  @doc("List principal groups.")
  listPrincipalGroups(): ListPrincipalGroupsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @doc("Create an principal group.")
  createPrincipalGroup(
    @body body: CreatePrincipalGroupRequestBody,
  ): CreatePrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{principalGroupId}/add-member")
  @doc("Add an identity to an principal group.")
  addPrincipalToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{principalGroupId}/remove-member")
  @doc("Remove an identity from an principal group.")
  removePrincipalFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{principalGroupId}")
  @doc("Delete an principal group.")
  deletePrincipalGroup(
    @path principalGroupId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
