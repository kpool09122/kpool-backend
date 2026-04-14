using TypeSpec.Http;

namespace KPool.AccountApi;

@doc("Request body for creating invitations.")
model CreateInvitationRequestBody {
  @doc("Account identifier that owns the invitations.")
  accountIdentifier: Uuid;
  @doc("Identity identifier sending the invitations.")
  inviterIdentityIdentifier: Uuid;
  @doc("Invitee email addresses.")
  emails: string[];
}

@doc("Response returned when invitations are created.")
model CreateInvitationResponse {
  @statusCode statusCode: 201;
  @body body: InvitationSummary[];
}

@route("/invitations")
interface InvitationOperations {
  @post
  @doc("Create invitations for one or more email addresses.")
  createInvitation(
    @body body: CreateInvitationRequestBody,
  ): CreateInvitationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
