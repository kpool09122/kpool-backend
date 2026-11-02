using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Analyze API" })
@server("/api/v1/analyze", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.AnalyzeApi;

@doc("New Analyze principal for the authenticated identity in the current account.")
model PrincipalSummary {
  principalIdentifier: Uuid;
  identityIdentifier: Uuid;
}

model CreatePrincipalResponse {
  @statusCode statusCode: 201;
  @body body: PrincipalSummary;
}

interface PrincipalOperations {
  @route("/principals")
  @useAuth(SessionCookieAuth)
  @post
  @doc("Enable Analyze for the authenticated identity and current account. No caller-supplied identity or account is accepted. Requires active direct account membership, joins the account's General group. General's default role allows viewing every analysis, including other owners' Wikis and ALL, but suspended/forced-stop principals, missing grants and explicit Deny are rejected. Duplicate enrollment returns 409 without restoring permissions. Authorization cleanup never deletes analysis data.")
  createPrincipal(): CreatePrincipalResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | ConflictProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;

  @route("/principal/me")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Inspect the authenticated identity's Analyze principal in the current account, including suspended/forced-stop states. Returns 404 before Analyze enrollment. Returns the principal status and effective policies.")
  getCurrentPrincipal(): GetCurrentPrincipalResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;
}

model PolicyStatement {
  effect: "allow" | "deny";
  actions: "analysis:view"[];
  resourceTypes: "analysis"[];
}

model PolicySummary {
  policyIdentifier: Uuid;
  name: string;
  statements: PolicyStatement[];
}

model PrincipalSnapshot {
  principalIdentifier: Uuid;
  identityIdentifier: Uuid;
  status: "active" | "suspended" | "forced_stop";
  policies: PolicySummary[];
}

model GetCurrentPrincipalResponse {
  @statusCode statusCode: 200;
  @body body: PrincipalSnapshot;
}
