using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Request body for creating a wiki draft.")
model CreateWikiRequestBody extends WikiAssociationTargets {
  @doc("Resource type that the wiki belongs to.")
  resourceType: "agency" | "group" | "talent" | "song";
  @doc("Language of the wiki.")
  language: string;
  @doc("Use the resource prefix ag-, gr-, tl-, or sg-, followed by lowercase letters, digits and single hyphens. Unique within the resource type among published wikis, regardless of language; branching excludes its translation set.")
  @minLength(3) @maxLength(80) @pattern("^(ag|gr|tl|sg)-[a-z0-9]+(-[a-z0-9]+)*$")
  slug: string;
  @doc("Basic wiki content payload.")
  basic: WikiBasicInput;
  @doc("Representative image identifier for the wiki.")
  imageIdentifier?: Uuid | null;
  @doc("Section content payloads.")
  sections?: WikiContentInput[] | null;
  @doc("Theme color applied to the wiki.")
  themeColor?: WikiHexColor | null;
  @doc("Stable font style identifier applied to the wiki.")
  fontStyle?: WikiFontStyle | null;
  @doc("SEO title for the wiki.")
  title?: SeoTitleText | null;
  @doc("SEO meta description for the wiki.")
  metaDescription?: MetaDescriptionText | null;
  @doc("SEO keywords for the wiki.")
  @maxItems(5)
  keywords?: SeoKeywordText[] | null;
  @doc("Published wiki identifier to branch from.")
  publishedWikiIdentifier?: Uuid | null;
}

@doc("Request body for automatically creating a wiki draft.")
model AutoCreateWikiRequestBody {
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Slug must be unique within the resource type among published wikis, regardless of language. Duplicates return 409 before AI generation.")
  slug: string;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Optional research context for AI generation, such as former group affiliations.")
  @maxLength(2000)
  notes?: string | null;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid | null;
  @doc("Associated group identifiers.")
  groupIdentifiers?: Uuid[];
  @doc("Associated talent identifiers.")
  talentIdentifiers?: Uuid[];
}

@doc("Request body for updating a wiki draft.")
model UpdateWikiDraftRequestBody extends WikiAssociationTargets {
  @doc("Resource type that the wiki belongs to.")
  resourceType: "agency" | "group" | "talent" | "song";
  @doc("Basic wiki content payload.")
  basic: WikiBasicInput;
  @doc("Representative image identifier for the wiki.")
  imageIdentifier?: Uuid | null;
  @doc("Section content payloads.")
  sections?: WikiContentInput[] | null;
  @doc("Theme color applied to the wiki.")
  themeColor?: WikiHexColor | null;
  @doc("Stable font style identifier applied to the wiki.")
  fontStyle?: WikiFontStyle | null;
  @doc("SEO title for the wiki.")
  title?: SeoTitleText | null;
  @doc("SEO meta description for the wiki.")
  metaDescription?: MetaDescriptionText | null;
  @doc("SEO keywords for the wiki.")
  @maxItems(5)
  keywords?: SeoKeywordText[] | null;
}

@doc("Request body for wiki workflow actions.")
model WikiWorkflowRequestBody extends WikiAssociationTargets {
  @doc("Resource type that the wiki belongs to.")
  resourceType: "agency" | "group" | "talent" | "song";
}

model SubmitWikiTarget {
  ...OmitProperties<WikiWorkflowRequestBody, "groupIdentifiers" | "talentIdentifiers">;
  wikiId: Uuid;
  @doc("Associated group identifiers.")
  groupIdentifiers?: Uuid[] | null;
  @doc("Associated talent identifiers.")
  talentIdentifiers?: Uuid[] | null;
}

model SubmitWikisRequestBody {
  @minItems(1)
  @maxItems(100)
  submissions: SubmitWikiTarget[];
}

model SubmitWikisResponse {
  @statusCode statusCode: 201;
  @body body: { draftWikis: DraftWikiSummary[]; };
}

@doc("Request body for rejecting a wiki draft.")
model RejectWikiRequestBody extends WikiAssociationTargets {
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Reason for rejecting the draft wiki.")
  rejectionReason: DraftWikiRejectionReasonText;
}

@doc("Request body for deleting a wiki draft.")
model DeleteWikiRequestBody {
  ...OmitProperties<WikiAssociationTargets, "groupIdentifiers" | "talentIdentifiers">;
  groupIdentifiers?: Uuid[] | null;
  talentIdentifiers?: Uuid[] | null;
}

@doc("Request body for withdrawing a wiki draft review request.")
model WithdrawWikiRequestBody {
  ...OmitProperties<WikiAssociationTargets, "groupIdentifiers" | "talentIdentifiers">;
  groupIdentifiers?: Uuid[] | null;
  talentIdentifiers?: Uuid[] | null;
}

@doc("Request body for rolling back a wiki to a previous version.")
model RollbackWikiRequestBody extends WikiAssociationTargets {
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Version number to roll back to.")
  targetVersion: int32;
}

@doc("Response returned when a wiki draft is created or updated.")
model CreateDraftWikiResponse {
  @statusCode statusCode: 201;
  @body body: DraftWikiSummary;
}

@doc("Response returned when a group draft wiki is fetched.")
model GetGroupDraftWikiResponse {
  @statusCode statusCode: 200;
  @body body: DraftWikiDetail;
}

@doc("Response returned when a group published wiki is fetched.")
model GetGroupWikiResponse {
  @statusCode statusCode: 200;
  @body body: WikiDetail;
}

@doc("Response returned when a talent draft wiki is fetched.")
model GetTalentDraftWikiResponse {
  @statusCode statusCode: 200;
  @body body: TalentDraftWikiDetail;
}

@doc("Response returned when a talent published wiki is fetched.")
model GetTalentWikiResponse {
  @statusCode statusCode: 200;
  @body body: TalentWikiDetail;
}

@doc("Response returned when a song draft wiki is fetched.")
model GetSongDraftWikiResponse {
  @statusCode statusCode: 200;
  @body body: SongDraftWikiDetail;
}

@doc("Response returned when a song published wiki is fetched.")
model GetSongWikiResponse {
  @statusCode statusCode: 200;
  @body body: SongWikiDetail;
}

@doc("Response returned when an agency draft wiki is fetched.")
model GetAgencyDraftWikiResponse {
  @statusCode statusCode: 200;
  @body body: AgencyDraftWikiDetail;
}

@doc("Response returned when an agency published wiki is fetched.")
model GetAgencyWikiResponse {
  @statusCode statusCode: 200;
  @body body: AgencyWikiDetail;
}

@doc("Response returned when a wiki is published.")
model CreatePublishedWikiResponse {
  @statusCode statusCode: 201;
  @body body: PublishedWikiSummary;
}

@doc("Response body for wiki translation results.")
model TranslateWikiResponseBody {
  @doc("Draft wikis generated by the translation action.")
  draftWikis: DraftWikiSummary[];
}

@doc("Response returned when wiki translation succeeds.")
model TranslateWikiResponse {
  @statusCode statusCode: 201;
  @body body: TranslateWikiResponseBody;
}

@doc("Response body for wiki rollback results.")
model RollbackWikiResponseBody {
  @doc("Published wikis produced by the rollback action.")
  wikis: PublishedWikiSummary[];
}

@doc("Response returned when wiki rollback succeeds.")
model RollbackWikiResponse {
  @statusCode statusCode: 201;
  @body body: RollbackWikiResponseBody;
}

@doc("Wiki list item returned by the wiki list endpoint.")
model WikiListItem {
  @doc("Wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Translation set identifier.")
  translationSetIdentifier: Uuid;
  @doc("Unique slug for the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number.")
  version: int32;
  @doc("Theme color applied to the wiki.")
  themeColor: WikiHexColor | null;
  @doc("Stable font style identifier applied to the wiki.")
  fontStyle: WikiFontStyle | null;
  @doc("SEO title for the wiki.")
  title: SeoTitleText | null;
  @doc("SEO meta description for the wiki.")
  metaDescription: MetaDescriptionText | null;
  @doc("SEO keywords for the wiki.")
  @maxItems(5)
  keywords: SeoKeywordText[] | null;
  @doc("Representative image identifier for the wiki.")
  imageIdentifier: Uuid | null;
  @doc("Absolute URL of the representative image.")
  imageUrl: string | null;
  @doc("Alternative text of the representative image.")
  imageAltText: string | null;
  @doc("Whether the representative image is hidden.")
  isHidden: boolean | null;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Normalized name used for search.")
  normalizedName: string;
  @doc("Published timestamp.")
  publishedAt: string | null;
  @doc("Last updated timestamp.")
  updatedAt: string | null;
}

@doc("Related profile card item returned by the related profiles endpoint.")
model RelatedProfileItem {
  @doc("Wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug for the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Normalized name used for search.")
  normalizedName: string;
  @doc("Representative image identifier for the wiki.")
  imageIdentifier: Uuid | null;
  @doc("Absolute URL of the representative image.")
  imageUrl: string | null;
  @doc("Alternative text of the representative image.")
  imageAltText: string | null;
}

@doc("Approval status assigned to a draft wiki.")
union DraftWikiStatus {
  "approved",
  "pending",
  "rejected",
  "under_review",
}

@doc("Draft wiki list item returned by the draft wiki list endpoint.")
model DraftWikiListItem {
  @doc("Draft wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Published wiki identifier linked to this draft.")
  publishedWikiIdentifier: Uuid | null;
  @doc("Translation set identifier.")
  translationSetIdentifier: Uuid;
  @doc("Unique slug for the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Theme color applied to the wiki.")
  themeColor: WikiHexColor | null;
  @doc("Stable font style identifier applied to the wiki.")
  fontStyle: WikiFontStyle | null;
  @doc("SEO title for the draft wiki.")
  title: SeoTitleText | null;
  @doc("SEO meta description for the draft wiki.")
  metaDescription: MetaDescriptionText | null;
  @doc("SEO keywords for the draft wiki.")
  @maxItems(5)
  keywords: SeoKeywordText[] | null;
  @doc("Approval status of the draft wiki.")
  status: DraftWikiStatus;
  @doc("Reason for the latest rejection of the draft wiki.")
  rejectionReason: DraftWikiRejectionReasonText | null;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Normalized name used for search.")
  normalizedName: string;
  @doc("Representative image identifier for the draft wiki.")
  imageIdentifier: Uuid | null;
  @doc("Absolute URL of the representative image.")
  imageUrl: string | null;
  @doc("Alternative text of the representative image.")
  imageAltText: string | null;
  @doc("Whether the representative image is hidden.")
  isHidden: boolean | null;
  @doc("Timestamp when the draft was edited.")
  editedAt: string | null;
  @doc("Timestamp when the draft was approved.")
  approvedAt: string | null;
  @doc("Timestamp when the draft was translated.")
  translatedAt: string | null;
  @doc("Timestamp when the draft was merged.")
  mergedAt: string | null;
}

@doc("Wiki master search candidate returned by the master search endpoint.")
model WikiMasterSearchItem {
  @doc("Wiki identifier.")
  id: Uuid;
  @doc("Display name of the wiki master.")
  name: string;
  @doc("Unique slug for the wiki.")
  slug: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
}

@doc("Response body for the wiki list endpoint.")
model ListWikisResponseBody {
  @doc("Wiki list for the requested page.")
  wikis: WikiListItem[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of matching wikis.")
  total: int32;
  @doc("Number of wikis per page.")
  per_page: int32;
}


@doc("Wiki item included in a translation set master search candidate.")
model TranslationSetMasterSearchWikiItem {
  @doc("Wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Language of the wiki.")
  language: string;
  @doc("Display name of the wiki master.")
  name: string;
  @doc("Unique slug for the wiki.")
  slug: string;
}

@doc("Translation set master search candidate returned by the official certification master search endpoint.")
model TranslationSetMasterSearchItem {
  @doc("Translation set identifier.")
  translationSetIdentifier: Uuid;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Wikis in the translation set, across all languages.")
  wikis: TranslationSetMasterSearchWikiItem[];
}

@doc("Response body for the translation set master search endpoint.")
model SearchTranslationSetMasterWikisResponseBody {
  @doc("Candidate translation set master wikis.")
  translationSetMasters: TranslationSetMasterSearchItem[];
}

@doc("Response body for the wiki master search endpoint.")
model SearchMasterWikisResponseBody {
  @doc("Candidate master wikis.")
  wikis: WikiMasterSearchItem[];
}

@doc("Response body for the current account owned wiki list endpoint.")
model ListMyOwnedWikisResponseBody {
  @doc("Category of the current account.")
  accountCategory: "agency" | "talent" | "general";
  @doc("Owned wikis whose resource type directly matches the current account category.")
  primaryOwnedWikis: WikiListItem[];
  @doc("Owned wikis whose resource type does not directly match the current account category for the requested page.")
  otherOwnedWikis: WikiListItem[];
  @doc("Current page number for other owned wikis.")
  current_page: int32;
  @doc("Last page number for other owned wikis.")
  last_page: int32;
  @doc("Total number of other owned wikis.")
  total: int32;
  @doc("Number of other owned wikis per page.")
  per_page: int32;
}

@doc("Response body for the draft wiki list endpoint.")
model ListDraftWikisResponseBody {
  @doc("Draft wikis for the requested page.")
  wikis: DraftWikiListItem[];
  @doc("Opaque cursor for the next batch; null when no more items remain.")
  next_cursor: string | null;
  @doc("Total number of draft wikis.")
  total: int32;
  @doc("Number of wikis per page.")
  per_page: int32;
}

@doc("Response body for the related profiles endpoint.")
model ListRelatedProfilesResponseBody {
  @doc("Related profile cards.")
  profiles: RelatedProfileItem[];
}

@doc("Response body for the related wikis endpoint.")
model ListRelatedWikisResponseBody {
  @doc("Related published wikis.")
  wikis: WikiListItem[];
}

@doc("Response returned when wiki list retrieval succeeds.")
model ListWikisResponse {
  @statusCode statusCode: 200;
  @body body: ListWikisResponseBody;
}

@doc("Response returned when wiki master search succeeds.")
model SearchMasterWikisResponse {
  @statusCode statusCode: 200;
  @body body: SearchMasterWikisResponseBody;
}


@doc("Response returned when translation set master search succeeds.")
model SearchTranslationSetMasterWikisResponse {
  @statusCode statusCode: 200;
  @body body: SearchTranslationSetMasterWikisResponseBody;
}

@doc("Response returned when related profile retrieval succeeds.")
model ListRelatedProfilesResponse {
  @statusCode statusCode: 200;
  @body body: ListRelatedProfilesResponseBody;
}

@doc("Response returned when related wiki retrieval succeeds.")
model ListRelatedWikisResponse {
  @statusCode statusCode: 200;
  @body body: ListRelatedWikisResponseBody;
}

@doc("Response returned when current account owned wiki list retrieval succeeds.")
model ListMyOwnedWikisResponse {
  @statusCode statusCode: 200;
  @body body: ListMyOwnedWikisResponseBody;
}

@doc("Response returned when draft wiki list retrieval succeeds.")
model ListDraftWikisResponse {
  @statusCode statusCode: 200;
  @body body: ListDraftWikisResponseBody;
}

@doc("Optional Worker-signed visitor location. Invalid or unsigned metadata is ignored; existing authentication remains required.")
model VisitorLocationHeaders {
  @header("X-Kpool-Visitor-Country") visitorCountry?: string | null;
  @header("X-Kpool-Visitor-Region") visitorRegion?: string | null;
  @header("X-Kpool-Visitor-Timestamp") visitorTimestamp?: string | null;
  @header("X-Kpool-Visitor-Signature") visitorSignature?: string | null;
}

@doc("Lightweight published wiki entry for frontend-owned sitemap URL and XML generation.")
model SitemapWiki {
  slug: string;
  language: string;
  resourceType: "agency" | "group" | "talent" | "song";
  @doc("ISO 8601 timestamp from published_at, refreshed on first publication and every republication (including same-version publication). Unrelated database updates do not affect it. Null when the publication time is unknown; do not fall back to updated_at.")
  lastModifiedAt: utcDateTime | null;
}

model ListSitemapWikisResponseBody {
  @maxItems(1000)
  wikis: SitemapWiki[];
  @doc("Pass this value as cursor for the next request. Null means enumeration is complete, including an empty result and an exactly full final page.")
  nextCursor: Uuid | null;
}

model ListSitemapWikisResponse {
  @statusCode statusCode: 200;
  @body body: ListSitemapWikisResponseBody;
}

@route("/sitemap/wikis")
interface SitemapWikiOperations {
  @get
  @doc("Enumerate published agency/group/talent/song wikis in all languages, ordered by immutable wiki UUID ascending. No authentication is required; the existing screen/query IP rate limit applies (default 300 requests/minute, also subject to the global query limit). Fetch the first page without cursor, then repeat using nextCursor until null. Deleted cursor rows remain valid boundaries. No cross-request snapshot is guaranteed: concurrent publications below an already visited cursor appear on the next enumeration started without cursor. Drafts and history are never included. Only sitemap fields are returned; no content, images or basic information is loaded.")
  listSitemapWikis(
    @doc("Maximum entries per response. Default 500; range 1..1000. The server reads at most perPage + 1 lightweight rows to detect the end.")
    @minValue(1) @maxValue(1000) @query perPage?: int32 | null,
    @doc("Exclusive UUID boundary returned as nextCursor. Omit or set null to start a new enumeration.")
    @query cursor?: Uuid | null,
  ): ListSitemapWikisResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/wikis")
interface WikiListOperations {
  @get
  @route("/version-inconsistencies")
  @doc("List published wikis with version inconsistencies across translation sets.")
  listVersionInconsistentWikis(
    @query perPage?: int32,
    @query resourceType?: string,
    @query sort?: string,
    @query order?: string,
  ): ListWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/{language}/masters")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Search published wiki masters for lightweight selection candidates. Internal API Bearer scope: wiki-drafts:read.")
  searchMasterWikis(
    @path language: string,
    @query resourceType: "agency" | "group" | "talent" | "song",
    @query keyword: string,
    @query limit?: int32,
  ): SearchMasterWikisResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/statistics")
  @useAuth(NoAuth)
  @doc("Public wiki page counts across all languages, with resource and group type breakdowns per language in byLanguage. Each translation counts separately; group types count group wiki pages. Drafts are excluded.")
  getWikiStatistics(): {
    @statusCode statusCode: 200;
    @body body: {
      total: int32;
      languages: { ja: int32; en: int32; ko: int32; };
      byLanguage: {
        ja: WikiStatisticsBreakdown;
        en: WikiStatisticsBreakdown;
        ko: WikiStatisticsBreakdown;
      };
    };
  } | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/{language}")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("List published wikis by language. Internal API Bearer scope: wiki:read.")
  listWikis(
    @path language: string,
    @doc("Page number; default 1. Uses the existing Laravel paginator.")
    @minValue(1) @query page?: int32 | null,
    @minValue(1) @maxValue(100) @query perPage?: int32 | null,
    @query resourceType?: string,
    @query keyword?: string,
    @doc("Sort field. Use createdAt for newly created wikis and version for most frequently updated wikis.")
    @query sort?: "updatedAt" | "name" | "createdAt" | "version" | null,
    @query order?: string,
  ): ListWikisResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;
}


@route("/wiki-translation-sets")
interface WikiTranslationSetOperations {
  @get
  @route("/masters")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Search wiki masters grouped by translation set for official certification requests. Internal API Bearer scope: wiki-drafts:read.")
  searchTranslationSetMasterWikis(
    @query resourceType: "agency" | "group" | "talent" | "song",
    @query keyword: string,
    @query limit?: int32,
  ): SearchTranslationSetMasterWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.ForbiddenProblemResponse;
}

@route("/my/owned-wikis")
interface MyOwnedWikiListOperations {
  @get
  @doc("List published wikis owned by the current account, grouped by account-category relevance.")
  listMyOwnedWikis(
    @query perPage?: int32,
  ): ListMyOwnedWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/my/draft-wikis")
interface MyDraftWikiListOperations {
  @get
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("List draft wikis edited by the current principal. Internal API Bearer scope: wiki-drafts:read.")
  listMyDraftWikis(
    @doc("Opaque next_cursor from the previous response. Omit for the first batch.")
    @maxLength(2048) @query cursor?: string | null,
    @minValue(1) @maxValue(100) @query perPage?: int32 | null,
    @query translationSetIdentifier?: Uuid,
    @minItems(1) @query statuses: DraftWikiStatus[],
    @query resourceType?: string,
  ): ListDraftWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.ForbiddenProblemResponse;
}

@route("/draft-wikis")
interface DraftWikiListOperations {
  @get
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("List draft wikis with read policy authorization. Internal API Bearer scope: wiki-drafts:read.")
  listDraftWikis(
    @doc("Opaque next_cursor from the previous response. Omit for the first batch.")
    @maxLength(2048) @query cursor?: string | null,
    @minValue(1) @maxValue(100) @query perPage?: int32 | null,
    @query translationSetIdentifier?: Uuid,
    @minItems(1) @query statuses: DraftWikiStatus[],
    @query resourceType?: string,
  ): ListDraftWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/wiki")
interface WikiOperations {
  @post
  @route("/create")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Create a new wiki draft. Internal API Bearer scope: wiki-drafts:write.")
  createWiki(
    @body body: CreateWikiRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/auto-create")
  @doc("Automatically create a wiki draft from the provided metadata.")
  @useAuth(SessionCookieAuth)
  autoCreateWiki(
    @body body: AutoCreateWikiRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/{wikiId}")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Delete a pending or rejected wiki draft. Internal API Bearer scope: wiki-drafts:write.")
  deleteWiki(
    @path wikiId: Uuid,
    @body body?: DeleteWikiRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/{wikiId}/approve")
  @doc("Approve a wiki draft.")
  approveWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: WikiWorkflowRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{wikiId}/edit")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Edit an existing wiki draft. Internal API Bearer scope: wiki-drafts:write.")
  editWiki(
    @path wikiId: Uuid,
    @body body: UpdateWikiDraftRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/{wikiId}/merge")
  @doc("Merge changes into an existing wiki draft.")
  mergeWiki(
    @path wikiId: Uuid,
    @body body: UpdateWikiDraftRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{wikiId}/publish")
  @doc("Publish an approved wiki draft as the next generation. Translated drafts catch up to the latest published version. Returns 409 when the selected publication version is inconsistent.")
  publishWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: WikiWorkflowRequestBody,
  ): CreatePublishedWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{wikiId}/publish-same-version")
  @doc("Publish an approved draft into its linked published wiki without changing its identifier or version. Other languages are unchanged. wikiId is the draft ID. Returns 409 for an unapproved draft or a draft without a published-wiki link, 404 when the draft or linked published wiki does not exist, and 403 for insufficient publish permission.")
  publishWikiAtSameVersion(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: WikiWorkflowRequestBody,
  ): CreatePublishedWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{wikiId}/reject")
  @doc("Reject a wiki draft.")
  rejectWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: RejectWikiRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{wikiId}/rollback")
  @doc("Roll back a wiki to a previous published version.")
  rollbackWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: RollbackWikiRequestBody,
  ): RollbackWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/submit")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Submit 1 to 100 distinct wiki drafts for review atomically, in request order. Any invalid, missing, disallowed or non-submittable draft rejects the entire batch; no status changes or histories are saved. Empty, oversized or duplicate submissions return 422. Internal API Bearer scope: wiki-drafts:submit.")
  submitWikis(
    ...VisitorLocationHeaders,
    @body body: SubmitWikisRequestBody,
  ): SubmitWikisResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/{wikiId}/submit")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Submit a wiki draft for review. Internal API Bearer scope: wiki-drafts:submit.")
  submitWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body: WikiWorkflowRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/{wikiId}/withdraw")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Withdraw a wiki draft review request, returning it from under review to pending. Internal API Bearer scope: wiki-drafts:write.")
  withdrawWiki(
    ...VisitorLocationHeaders,
    @path wikiId: Uuid,
    @body body?: WithdrawWikiRequestBody,
  ): CreateDraftWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse;

  @post
  @route("/{wikiId}/translate")
  @doc("Translate a wiki draft into additional languages.")
  translateWiki(
    @path wikiId: Uuid,
    @body body: WikiWorkflowRequestBody,
  ): TranslateWikiResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/{language}/{slug}/related-profiles")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("List related published wiki profiles for a source wiki. Internal API Bearer scope: wiki:read.")
  listRelatedProfiles(
    @path language: string,
    @path slug: string,
    @query resourceType: string,
  ): ListRelatedProfilesResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{resourceType}/{translationSetIdentifier}/related-wikis")
  @doc("List related published wikis for an agency or talent translation set.")
  listRelatedWikis(
    @path resourceType: "agency" | "talent",
    @path translationSetIdentifier: Uuid,
  ): ListRelatedWikisResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/{language}/group/{slug}")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("Fetch the published group wiki. Internal API Bearer scope: wiki:read.")
  getGroupWiki(
    @path language: string,
    @path slug: string,
  ): GetGroupWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/group/{wikiIdentifier}/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch a group draft wiki by draft wiki identifier. Requires READ Policy on the target draft resource using the current Account/delegation Wiki Principal. Returns 403 when READ is denied and 404 when the draft or Principal does not exist. Internal API Bearer scope: wiki-drafts:read.")
  getGroupDraftWiki(
    @path wikiIdentifier: Uuid,
  ): GetGroupDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/talent/{slug}")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("Fetch the published talent wiki. Internal API Bearer scope: wiki:read.")
  getTalentWiki(
    @path language: string,
    @path slug: string,
  ): GetTalentWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/talent/{wikiIdentifier}/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch a talent draft wiki by draft wiki identifier. Requires READ Policy on the target draft resource using the current Account/delegation Wiki Principal. Returns 403 when READ is denied and 404 when the draft or Principal does not exist. Internal API Bearer scope: wiki-drafts:read.")
  getTalentDraftWiki(
    @path wikiIdentifier: Uuid,
  ): GetTalentDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/song/{slug}")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("Fetch the published song wiki. Internal API Bearer scope: wiki:read.")
  getSongWiki(
    @path language: string,
    @path slug: string,
  ): GetSongWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/song/{wikiIdentifier}/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch a song draft wiki by draft wiki identifier. Requires READ Policy on the target draft resource using the current Account/delegation Wiki Principal. Returns 403 when READ is denied and 404 when the draft or Principal does not exist. Internal API Bearer scope: wiki-drafts:read.")
  getSongDraftWiki(
    @path wikiIdentifier: Uuid,
  ): GetSongDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/agency/{slug}")
  @useAuth(NoAuth | InternalApiBearerAuth)
  @doc("Fetch the published agency wiki. Internal API Bearer scope: wiki:read.")
  getAgencyWiki(
    @path language: string,
    @path slug: string,
  ): GetAgencyWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/agency/{wikiIdentifier}/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch an agency draft wiki by draft wiki identifier. Requires READ Policy on the target draft resource using the current Account/delegation Wiki Principal. Returns 403 when READ is denied and 404 when the draft or Principal does not exist. Internal API Bearer scope: wiki-drafts:read.")
  getAgencyDraftWiki(
    @path wikiIdentifier: Uuid,
  ): GetAgencyDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;
}

@route("/wiki")
interface MyWikiOperations {
  @get
  @route("/{language}/group/{slug}/my/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch the current principal's group draft wiki by language and slug. Internal API Bearer scope: wiki-drafts:read.")
  getMyGroupDraftWiki(
    @path language: string,
    @path slug: string,
  ): GetGroupDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/talent/{slug}/my/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch the current principal's talent draft wiki by language and slug. Internal API Bearer scope: wiki-drafts:read.")
  getMyTalentDraftWiki(
    @path language: string,
    @path slug: string,
  ): GetTalentDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/song/{slug}/my/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch the current principal's song draft wiki by language and slug. Internal API Bearer scope: wiki-drafts:read.")
  getMySongDraftWiki(
    @path language: string,
    @path slug: string,
  ): GetSongDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/{language}/agency/{slug}/my/draft")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Fetch the current principal's agency draft wiki by language and slug. Internal API Bearer scope: wiki-drafts:read.")
  getMyAgencyDraftWiki(
    @path language: string,
    @path slug: string,
  ): GetAgencyDraftWikiResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse;
}

model WikiStatisticsBreakdown {
  resourceTypes: { agency: int32; group: int32; talent: int32; song: int32; };
  groupTypes: { boy_group: int32; girl_group: int32; co_ed: int32; `unknown`: int32; };
}
