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

@doc("Comparison with the immediately preceding equal number of UTC days under identical filters. Percentage values are decimal strings rounded half-up to one decimal place. Counts use period totals, registeredUsers uses closing balances, engagementTime uses each period's summed numerator divided by its summed denominator. Uses available period aggregates even when daily data is partial or missing. Null period aggregates are not zero; null rates distinguish missing previous/current data, a zero baseline and unavailable average denominators. Operation actors use period-distinct counts.")
model PeriodComparison {
  from: plainDate;
  to: plainDate;
  percentageChange: string | null;
  reason: ComparisonReason | null;
  actorPercentageChange: string | null;
  actorReason: ComparisonReason | null;
}

alias ComparisonReason = "no_previous_data" | "no_current_data" | "zero_baseline" | "invalid_denominator" | "not_applicable";

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
  comparison: PeriodComparison | null;
}

model AppDailySummaryReport {
  @doc("Sum of independently acquired daily application users (person-days), with separately acquired country breakdowns. Global users are not the sum of countries.")
  applicationUsers: MetricReport;
  @doc("Identity closing balance on the final selected day, grouped by immutable registration-time access country. Missing registration country is unknown; account addresses are never used. Wiki/language filters do not apply.")
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
  @doc("Get all App metrics for an inclusive UTC period of at most six calendar months ending no later than UTC D-3. Stored historical periods have no age cutoff. Requires active Account/Account principal and enrolled active Analyze principal with analysis:view. General grants all analyses; explicit Deny wins. Authorization is re-evaluated on every request. country=unknown selects unknown; omitted/null selects all. Country breakdowns are unavailable for imports predating country collection. Never returns individual viewer/actor identifiers. Invalid period: 400; invalid query format: 422.")
  getAppDailySummaryReport(@query from: plainDate, @query to: plainDate, @query country?: string | null): AppDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;

  @route("/wiki/daily-summary")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Get all Wiki metrics under the App period/authorization rules, including other owners' Wikis. Omit both target identifiers for ALL; specify either Wiki or translation set, never both. language/country filter all Wiki metrics. country=unknown selects unknown; omitted/null selects all. Stored zero values are measured zero; no summary rows means not_collected, including complete empty imports. Import failures leave published measurements unchanged and are handled through error monitoring and period-specific command retries. Operations read histories independently of imports. Counts, time and viewer-days sum; actor counts are period-distinct Principals. Country unknown is null. Countries without summary/history rows are omitted; absence alone does not prove measured zero. Authorization is re-evaluated on every request.")
  getWikiDailySummaryReport(@query from: plainDate, @query to: plainDate, @query wikiIdentifier?: Uuid | null, @query translationSetIdentifier?: Uuid | null, @query language?: "ja" | "ko" | "en" | null, @query country?: string | null): WikiDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;
}

@doc("An exact source subdivision, never a synthetic parent roll-up. region is a country-prefixed ISO 3166-2/display-aligned code, or null for unknown. mapSupported only describes the AdministrativeAreaCode map enum, not storage/API validity. Names are not required. PH parent regions and provinces retain their original codes as separate grains; never add parent and child as if disjoint, or sum regional users into country users. CN/TW use their existing country-prefixed code conventions.")
model RegionMetric {
  region: string | null;
  mapSupported: boolean;
  value: string | null;
  denominator: int64 | null;
  quality: string[];
  actorCount?: string | null;
}

@doc("Same daily/total/snapshot/denominator/actor semantics as MetricReport, with regions instead of countries. With region omitted totals/days are independently acquired country measurements, NOT a sum of regions. With region selected they are exact-region measurements (definition grain suffix _region for GA4/Identity). Unknown and unmapped source region names use null region, with region_unknown / region_unmapped quality respectively; valid codes outside map coverage remain normal measurements. No stored regional row means not_collected/null, not measured zero. Import failure preserves published measurements and is not an availability state.")
model RegionalMetricReport {
  definition: MetricDefinition;
  revision: string | null;
  days: MetricDay[];
  total: string | null;
  denominator: int64 | null;
  actorCount: string | null;
  regions: RegionMetric[];
  comparison: PeriodComparison | null;
}

model AppRegionalDailySummaryReport {
  applicationUsers: RegionalMetricReport;
  registeredUsers: RegionalMetricReport;
}

model WikiRegionalDailySummaryReport {
  views: RegionalMetricReport;
  viewers: RegionalMetricReport;
  engagementTime: RegionalMetricReport;
  submissions: RegionalMetricReport;
  publications: RegionalMetricReport;
}

model AppRegionalDailySummaryResponse {
  @statusCode statusCode: 200;
  @body body: AppRegionalDailySummaryReport;
}

model WikiRegionalDailySummaryResponse {
  @statusCode statusCode: 200;
  @body body: WikiRegionalDailySummaryReport;
}

interface RegionalAnalysisOperations {
  @route("/app/regional-daily-summary")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Country required (ISO 3166-1 alpha-2). region omitted/null returns the country trend and exact-region breakdown; region=unknown selects unknown, otherwise a complete country-prefixed known subdivision code, e.g. JP-13 or DE-BE (valid even though DE is not in map coverage). Mismatched/invalid codes and missing country return 422. Same inclusive UTC period (at most six calendar months, ending no later than D-3; invalid period 400) and fresh analysis:view authorization as the country endpoint. Registered users are final-day closing balances attributed to immutable verified registration-time region, including archives, never account addresses. Old API remains unchanged. No raw viewer/actor identifiers are returned.")
  getAppRegionalDailySummaryReport(@query from: plainDate, @query to: plainDate, @query country: string, @query region?: string | null): AppRegionalDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;

  @route("/wiki/regional-daily-summary")
  @useAuth(SessionCookieAuth)
  @get
  @doc("Same country/region/period/authorization contract as the App regional endpoint. Wiki and translation set are mutually exclusive; omit both for ALL. language, target and region apply to every Wiki metric. Views/viewers are additive daily measurements, not globally deduplicated users. Engagement is the summed numerator and denominator (derive weighted average, never average regional averages). Operation actors are distinct Principals across the full selected period; never sum daily/region actor counts. Direct-history measured zero is distinct from missing/uncollected GA4 rows; missing history attribution is incomplete. Existing country endpoint never reads regional projections.")
  getWikiRegionalDailySummaryReport(@query from: plainDate, @query to: plainDate, @query country: string, @query region?: string | null, @query wikiIdentifier?: Uuid | null, @query translationSetIdentifier?: Uuid | null, @query language?: "ja" | "ko" | "en" | null): WikiRegionalDailySummaryResponse | BadRequestProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | TooManyRequestsProblemResponse | InternalServerErrorProblemResponse | ServiceUnavailableProblemResponse;
}
