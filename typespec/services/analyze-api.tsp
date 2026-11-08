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

@doc("Metric definition selected by the server; callers cannot mix sources or versions.")
model MetricDefinition {
  source: string;
  metric: string;
  grain: string;
  version: string;
}

@doc("UTC day. Availability describes stored measurements, not import execution. not_collected means no summary rows in the selected scope; it does not distinguish an empty import from an uncollected day. missing means an expected metric/definition is absent; incomplete also marks partial summary definitions. Direct history uses measured zero when empty, incomplete for missing attribution and null updatedAt. Import errors are reported operationally and leave published values and updatedAt unchanged.")
model MetricDay {
  day: plainDate;
  availability: "measured" | "not_collected" | "missing" | "incomplete";
  value: string | null;
  denominator: int64 | null;
  quality: string[];
  updatedAt: string | null;
  actorCount?: string | null;
}

@doc("World-map period aggregation, sorted by code with unknown first. Null country means unknown (empty string in storage). Country actor counts are distinct within that country; do not sum them for the global actor count.")
model CountryMetric {
  country: string | null;
  value: string | null;
  denominator: int64 | null;
  quality: string[];
  actorCount?: string | null;
}

model MetricReport {
  definition: MetricDefinition;
  revision: string | null;
  days: MetricDay[];
  @doc("Additive total except registeredUsers: final selected UTC day's Identity closing balance. Null is not zero. May be a partial known total: inspect all daily states/quality.")
  total: string | null;
  denominator: int64 | null;
  @doc("Distinct operation Principals over the full selection, never a sum of daily/country actors. Null for other metrics or incomplete attribution.")
  actorCount: string | null;
  countries: CountryMetric[];
}

model AppDailySummaryReport {
  @doc("Sum of independently acquired daily application users (person-days). No country projection.")
  applicationUsers: MetricReport;
  @doc("Identity closing balance on the final selected day; never filtered by Wiki/language/country. No country projection.")
  registeredUsers: MetricReport;
}

model WikiDailySummaryReport {
  views: MetricReport;
  viewers: MetricReport;
  engagementTime: MetricReport;
  submissions: MetricReport;
  publications: MetricReport;
}

model AppDailySummaryResponse {
  @statusCode statusCode: 200;
  @body body: AppDailySummaryReport;
}

model WikiDailySummaryResponse {
  @statusCode statusCode: 200;
  @body body: WikiDailySummaryReport;
}

model BadRequestProblemResponse {
  @statusCode statusCode: 400;
  @body body: ProblemDetails;
}

interface AnalysisOperations {
  @route("/app/daily-summary")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Get all App metrics for an inclusive UTC period of at most one calendar year ending no later than UTC D-3. Stored historical periods have no age cutoff. Requires active Account/Account principal and enrolled active Analyze principal with analysis:view. General grants all analyses; explicit Deny wins. Authorization is re-evaluated on every request. Never returns individual viewer/actor identifiers. Invalid period: 400; invalid query format: 422.")
  getAppDailySummaryReport(@query from: plainDate, @query to: plainDate): AppDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;

  @route("/wiki/daily-summary")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Get all Wiki metrics under the App period/authorization rules, including other owners' Wikis. Omit both target identifiers for ALL; specify either Wiki or translation set, never both. language/country filter all Wiki metrics. country=unknown selects unknown; omitted/null selects all. Stored zero values are measured zero; no summary rows means not_collected, including complete empty imports. Import failures leave published measurements unchanged and are handled through error monitoring and period-specific command retries. Operations read histories independently of imports. Counts, time and viewer-days sum; actor counts are period-distinct Principals. Country unknown is null. Countries without summary/history rows are omitted; absence alone does not prove measured zero. Authorization is re-evaluated on every request.")
  getWikiDailySummaryReport(@query from: plainDate, @query to: plainDate, @query wikiIdentifier?: Uuid | null, @query translationSetIdentifier?: Uuid | null, @query language?: "ja" | "ko" | "en" | null, @query country?: string | null): WikiDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;
}
