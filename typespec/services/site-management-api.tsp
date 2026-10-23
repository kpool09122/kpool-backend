using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Site Management API" })
@server("/api/v1/site-management", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.SiteManagementApi;

@doc("Contact category accepted by the submit-contact endpoint.")
enum ContactCategory {
  suggestions: 1,
  bugReport: 2,
  contentIssue: 3,
  other: 99,
}

@doc("Request body for submitting a contact inquiry.")
model SubmitContactRequestBody {
  @doc("Category of the inquiry.")
  category: ContactCategory;
  @doc("Name of the person submitting the inquiry.")
  name: string;
  @doc("Email address of the person submitting the inquiry.")
  email: string;
  @doc("Inquiry details.")
  content: string;
}

@doc("Submitted contact snapshot returned after creation.")
model ContactSummary {
  @doc("Contact identifier.")
  contactIdentifier: Uuid;
  @doc("Site-management principal identifier when the inquiry is associated with a signed-in user.")
  principalIdentifier?: Uuid | null;
  @doc("Category of the inquiry.")
  category: int32;
  @doc("Name of the person submitting the inquiry.")
  name: string;
  @doc("Email address of the person submitting the inquiry.")
  email: string;
  @doc("Inquiry details.")
  content: string;
}

@doc("Response returned when a contact inquiry is created.")
model SubmitContactResponse {
  @statusCode statusCode: 201;
  @body body: ContactSummary;
}

@doc("Contact inquiry summary returned to its submitter.")
model MyContactSummary {
  @doc("Contact identifier.")
  contactIdentifier: Uuid;
  @doc("Site-management principal identifier associated with the inquiry.")
  principalIdentifier?: Uuid | null;
  @doc("Category of the inquiry.")
  category: int32;
  @doc("Name of the person submitting the inquiry.")
  name: string;
  @doc("Identifiers of replies sent successfully for this inquiry.")
  replyIdentifiers: Uuid[];
  @doc("Timestamp when the inquiry was created.")
  createdAt: Timestamp;
  @doc("Full inquiry content.")
  content: string;
}

@doc("A successfully sent reply to a contact inquiry.")
model ContactReplyDetail {
  replyIdentifier: Uuid;
  content: string;
  sentAt: Timestamp;
}

@doc("Contact inquiry detail.")
model ContactDetail {
  contactIdentifier: Uuid;
  principalIdentifier?: Uuid | null;
  category: int32;
  name: string;
  createdAt: Timestamp;
  content: string;
  replies: ContactReplyDetail[];
}

@doc("A successfully sent reply including author information, returned only to a site-management Operator.")
model OperatorContactReplyDetail {
  ...ContactReplyDetail;
  @doc("SiteManagement Principal ID stored on this reply, retained even if the Principal no longer exists. Null when no ID was saved.")
  principalIdentifier: Uuid | null;
  @doc("Current Identity.identity_name of the stored Principal, not a historical name snapshot. Null if the Principal or Identity is unavailable; never supplemented with submitter, Account or viewer information.")
  name: string | null;
}

@doc("Contact detail returned to an Operator, with author information on each successfully sent reply. No email addresses are returned.")
model OperatorContactDetail {
  ...OmitProperties<ContactDetail, "replies">;
  replies: OperatorContactReplyDetail[];
}

@doc("Response containing contact inquiries associated with the signed-in principal.")
model ListMyContactsResponse {
  @statusCode statusCode: 200;
  @body body: MyContactSummary[];
}

@route("/my/contact")
interface MyContactOperations {
  @get
  @route("/{contactIdentifier}")
  @doc("Get a contact inquiry associated with the signed-in principal.")
  getMyContactDetail(
    @path contactIdentifier: Uuid,
  ): ContactDetail | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @doc("List contact inquiries associated with the signed-in principal, newest first.")
  listMyContacts(): ListMyContactsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@doc("Response containing contact inquiries for an principal.")
model ListContactsByPrincipalResponse {
  @statusCode statusCode: 200;
  @body body: MyContactSummary[];
}

@doc("Paginated contact inquiries and page metadata.")
model ListContactsPage {
  @doc("Contact inquiries on the requested page.")
  contacts: MyContactSummary[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last available page number.")
  last_page: int32;
  @doc("Total number of matching inquiries.")
  total: int32;
  @doc("Number of inquiries per page.")
  per_page: int32;
}

@doc("Response containing contact inquiries and pagination metadata.")
model ListContactsResponse {
  @statusCode statusCode: 200;
  @body body: ListContactsPage;
}

@doc("Request body for replying to a contact inquiry.")
model ReplyContactRequestBody {
  @doc("Non-empty reply content. No maximum length is imposed by ReplyContent.")
  @minLength(1)
  content: string;
}

@route("/contacts")
interface ContactListOperations {
  @useAuth(SessionCookieAuth)
  @get
  @route("/{contactIdentifier}")
  @doc("Get a contact inquiry by identifier, including anonymous submissions. Uses the same contact:reply policy checks as replying: evaluate without an owner before lookup, then against the persisted nullable owner. No Operator role is required independently of this policy. An ownerless denial returns 403 before lookup; a missing inquiry returns 404 after that check succeeds. Only Operators receive reply author information; other authorized principals receive the original three reply fields. No email addresses are returned.")
  getContactDetailByIdentifier(
    @path contactIdentifier: Uuid,
  ): ContactDetail | OperatorContactDetail | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @useAuth(SessionCookieAuth)
  @post
  @route("/{contactIdentifier}/replies")
  @doc("Send a reply to a contact inquiry. Requires a site-management Operator. Returns no payload; mail delivery failures retain failed reply history and return 500.")
  replyContact(
    @doc("UUIDv7 contact identifier, matching the existing ContactIdentifier domain constraint.")
    @path contactIdentifier: UuidV7,
    @body body: ReplyContactRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @doc("List all contact inquiries, optionally filtered by principal. Requires a site-management Operator.")
  listContacts(
    @query principalIdentifier?: Uuid | null,
    @doc("Reply-status filter: 1 for at least one successfully sent reply, 0 for none. When omitted (null), no reply-status filter is applied.")
    @query hasReply?: "0" | "1" | null,
    @doc("Maximum number of inquiries per page (1-100, default 50).")
    @minValue(1)
    @maxValue(100)
    @query perPage?: int32 | null,
    @doc("Page number, starting at 1 (default 1).")
    @minValue(1)
    @query page?: int32 | null,
  ): ListContactsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/contact")
interface ContactOperations {
  @get
  @route("/principals/{principalIdentifier}/{contactIdentifier}")
  @doc("Get the specified contact inquiry for a principal. Uses the same contact:reply policy checks as replying: evaluate without an owner before lookup, then against the persisted owner. No Operator role is required independently of this policy; General contact:view permission alone does not grant access, even to the caller's own inquiry. An ownerless denial returns 403 before lookup; a missing inquiry or principal mismatch returns 404 after that check succeeds. Only Operators receive reply author information; other authorized principals receive the original three reply fields.")
  getContactDetail(
    @path principalIdentifier: Uuid,
    @path contactIdentifier: Uuid,
  ): ContactDetail | OperatorContactDetail | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;


  @get
  @route("/principals/{principalIdentifier}")
  @doc("List contact inquiries for the specified principal. Requires a site-management Operator.")
  listContactsByPrincipal(
    @path principalIdentifier: Uuid,
  ): ListContactsByPrincipalResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/submit")
  @doc("Submit a contact inquiry.")
  submitContact(
    @body body: SubmitContactRequestBody,
  ): SubmitContactResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}


@doc("User information for a principal in the current Account, including users with no group memberships.")
model PrincipalGroupMember {
  @doc("Principal lifecycle status. Stopped users remain in management lists and retain their permissions.")
  status: PrincipalStatus;
  principalIdentifier: Uuid;
  identityIdentifier: Uuid;
  identityName: string;
  email: string;
}

@doc("Editable group. Groups containing the system Operator role, including mixed-role groups, are excluded.")
model PrincipalGroup {
  principalGroupIdentifier: Uuid;
  accountIdentifier: Uuid;
  name: string;
  description: string | null;
  roleIdentifiers: Uuid[];
  isDefault: boolean;
  members: PrincipalGroupMember[];
}

model PrincipalGroupList {
  principalGroups: PrincipalGroup[];
  principals: PrincipalGroupMember[];
}

model UpdatedPrincipalGroup {
  principalGroupIdentifier: Uuid;
  accountIdentifier: Uuid;
  name: string;
  description: string | null;
  isDefault: boolean;
  memberCount: int32;
}

model UpdatePrincipalGroupMembersResponse {
  principalGroups: UpdatedPrincipalGroup[];
  principalStatuses: PrincipalStatusUpdate[];
}

model UpdatePrincipalGroupMembersItem {
  principalGroupIdentifier: Uuid;
  principalIdentifiers: Uuid[];
}

model UpdatePrincipalGroupMembersRequestBody {
  principalGroups: UpdatePrincipalGroupMembersItem[];
  @doc("Statuses to save atomically with group memberships. Omission or an empty array makes no status changes; omitted Principals retain their status. All three values are accepted so unchanged forced_stop rows can be included.")
  principalStatuses?: PrincipalStatusUpdate[];
}

@route("/principal-groups")
@useAuth(SessionCookieAuth)
interface PrincipalGroupOperations {
  @get
  @doc("List all editable groups and all principals of the server-resolved current Account. Requires principal-group:manage on principal_group. No accountIdentifier query. Missing Principal or insufficient permission returns 403. Existing identity/auth/me provides the current Principal and policies.")
  listPrincipalGroups(): PrincipalGroupList | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @patch
  @route("/members")
  @doc("Atomically save specified group memberships and Principal statuses while preserving omitted groups and statuses. The final combined state must retain a reachable active permission manager; an active replacement and suspension of the old manager can be saved together. Duplicate status identifiers return 422; missing or out-of-scope Principals return 404; insufficient permission or protected Operator groups return 403. An unchanged forced_stop is accepted. A forbidden status change returns 409 code principal_status_change_forbidden; losing the last active manager returns 409 code last_active_manager. Both roll back the entire save. Success returns only the submitted groups and Principal statuses; clients refetch management lists separately. Status restrictions take effect on the next request even for logged-in cached sessions.")
  updatePrincipalGroupMembers(@body body: UpdatePrincipalGroupMembersRequestBody): UpdatePrincipalGroupMembersResponse | PrincipalStatusConflictProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}

@doc("Category shared by all language contents of an announcement.")
enum AnnouncementCategory {
  news: 1,
  updates: 2,
  maintenance: 3,
}

model AnnouncementLocalizedContent {
  @minLength(1)
  @maxLength(64)
  title: string;
  @minLength(1)
  @maxLength(2048)
  content: string;
}

@doc("Available contents by language. Untranslated languages are omitted. The source language is always present.")
model AnnouncementContents {
  ja?: AnnouncementLocalizedContent;
  ko?: AnnouncementLocalizedContent;
  en?: AnnouncementLocalizedContent;
}

@doc("One draft announcement with shared metadata and all available language contents. Incomplete translations are included for Operator review.")
model DraftAnnouncement {
  announcementIdentifier: UuidV7;
  sourceLanguage: "ja" | "ko" | "en";
  category: AnnouncementCategory;
  contents: AnnouncementContents;
}

model ListDraftAnnouncementsPage {
  announcements: DraftAnnouncement[];
  @minValue(1)
  current_page: int32;
  @minValue(1)
  last_page: int32;
  @minValue(0)
  total: int32;
  @minValue(1)
  @maxValue(100)
  per_page: int32;
}

model ListDraftAnnouncementsResponse {
  @statusCode statusCode: 200;
  @body body: ListDraftAnnouncementsPage;
}

@route("/draft-announcements")
@useAuth(SessionCookieAuth)
interface DraftAnnouncementOperations {
  @get
  @doc("List draft announcements, including untranslated drafts, with all available language contents. Requires announcement:view-draft on announcement, granted to the system Operator role. General and Administrator alone are denied. The acting principal is resolved from the session; no principal or Account identifier is accepted as a selector. Search matches titles and bodies across available languages. Laravel resolves the page query automatically. Matching drafts are ordered by the selected field and then by identifier for stable pagination.")
  listDraftAnnouncements(
    @doc("Number of drafts per page; default 50.")
    @minValue(1)
    @maxValue(100)
    @query perPage?: int32 | null,
    @doc("Laravel's standard current-page parameter; default 1.")
    @minValue(1)
    @query page?: int32 | null,
    @doc("Sort direction; default desc.")
    @query order?: "asc" | "desc" | null,
    @doc("Sort field; default created_at.")
    @query sort?: "created_at" | null,
    @doc("Literal substring to find in any available language title or body. Empty or null disables filtering.")
    @query searchWords?: string | null,
  ): ListDraftAnnouncementsResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get
  @route("/{announcementIdentifier}")
  @doc("Get a draft announcement with all available language contents. Requires announcement:view-draft on announcement, granted to the system Operator role. Authorization is checked before reading the draft. A missing or deleted draft, including an identifier that exists only as a published announcement, returns 404. Invalid identifiers return 422.")
  getDraftAnnouncement(
    @path announcementIdentifier: UuidV7,
  ): DraftAnnouncement | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}

@doc("One published announcement projected into the requested language. Missing translations are not substituted with another language.")
model PublishedAnnouncementSummary {
  announcement_id: UuidV7;
  category_name: "NEWS" | "UPDATES" | "MAINTENANCE";
  title: string;
  content: string;
  @format("date")
  published_date: DateString;
}

model ListAnnouncementsPage {
  announcements: PublishedAnnouncementSummary[];
  @minValue(1)
  current_page: int32;
  @minValue(1)
  last_page: int32;
  @minValue(0)
  total: int32;
  @minValue(1)
  @maxValue(100)
  per_page: int32;
}

model ListAnnouncementsResponse {
  @statusCode statusCode: 200;
  @body body: ListAnnouncementsPage;
}

@route("/announcements")
interface AnnouncementOperations {
  @get
  @doc("List published announcements without authentication. Filter by the requested language before pagination; drafts and unavailable translations are excluded. When language is omitted or null, resolve ja/ko/en from Accept-Language, falling back to English. Laravel resolves the page query automatically.")
  listAnnouncements(
    @query language?: "ja" | "ko" | "en" | null,
    @header("Accept-Language") acceptLanguage?: string,
    @doc("Number per page; default 50.")
    @minValue(1)
    @maxValue(100)
    @query perPage?: int32 | null,
    @minValue(1)
    @query page?: int32 | null,
    @doc("Default desc.")
    @query order?: "asc" | "desc" | null,
    @doc("Default published_date.")
    @query sort?: "published_date" | "title" | null,
    @doc("Literal substring of title or body in the selected language.")
    @query searchWords?: string | null,
  ): ListAnnouncementsResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get
  @route("/{announcementIdentifier}")
  @doc("Get a published announcement without authentication in the selected language. When language is omitted or null, resolve Accept-Language with an English fallback. A missing announcement, a draft-only identifier or an unavailable translation returns 404.")
  getAnnouncement(
    @path announcementIdentifier: UuidV7,
    @query language?: "ja" | "ko" | "en" | null,
    @header("Accept-Language") acceptLanguage?: string,
  ): PublishedAnnouncementSummary | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}

@doc("Create one draft with original content in language. sourceLanguage is set to this language. Publication date is assigned by the server when published.")
model CreateAnnouncementRequestBody {
  language: "ja" | "ko" | "en";
  category: AnnouncementCategory;
  @minLength(1)
  @maxLength(64)
  title: string;
  @minLength(1)
  @maxLength(2048)
  content: string;
}

@doc("Replace common category and the selected language's content on an existing draft. Other language contents and sourceLanguage are retained. Titles and bodies must not be blank.")
model UpdateAnnouncementRequestBody {
  language: "ja" | "ko" | "en";
  category: AnnouncementCategory;
  @minLength(1)
  @maxLength(64)
  title: string;
  @minLength(1)
  @maxLength(2048)
  content: string;
}

model CreateAnnouncementResponse {
  @statusCode statusCode: 201;
  @body body: DraftAnnouncement;
}

model TranslateAnnouncementResponse {
  @minItems(1)
  @maxItems(1)
  announcements: DraftAnnouncement[];
}

model PublishedAnnouncementContents {
  ja: AnnouncementLocalizedContent;
  ko: AnnouncementLocalizedContent;
  en: AnnouncementLocalizedContent;
}

model PublishedAnnouncement {
  announcementIdentifier: UuidV7;
  sourceLanguage: "ja" | "ko" | "en";
  category: AnnouncementCategory;
  contents: PublishedAnnouncementContents;
  @format("date")
  publishedDate: DateString;
}

model PublishAnnouncementResponse {
  @minItems(1)
  @maxItems(1)
  announcements: PublishedAnnouncement[];
}

@doc("The deleted draft, or an empty array if no draft existed. Published announcements are retained.")
model DeleteAnnouncementResponse {
  @maxItems(1)
  announcements: DraftAnnouncement[];
}

@route("/draft-announcements")
@useAuth(SessionCookieAuth)
interface DraftAnnouncementCommandOperations {
  @post
  @doc("Create a draft. Requires announcement:create on announcement. Acting principal is resolved from the session. Returns one draft with its generated identifier.")
  createAnnouncement(@body body: CreateAnnouncementRequestBody): CreateAnnouncementResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @put
  @route("/{announcementIdentifier}")
  @doc("Update an existing draft's common metadata and selected language content. Requires announcement:update on announcement. A missing draft returns 404.")
  updateAnnouncement(@path announcementIdentifier: UuidV7, @body body: UpdateAnnouncementRequestBody): DraftAnnouncement | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @post
  @route("/{announcementIdentifier}/translate")
  @doc("Fill only missing ja/ko/en contents from the draft's source language. Requires announcement:translate on announcement. Retains the same identifier, existing translations and manual edits. Saves only after all requested translations succeed. Translation-provider failure returns 500 and leaves the draft unchanged.")
  translateAnnouncement(@path announcementIdentifier: UuidV7): TranslateAnnouncementResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @post
  @route("/{announcementIdentifier}/publish")
  @doc("Publish a draft under the same identifier. The server assigns the current date and time as the publication date on each publication. Requires announcement:publish on announcement. Non-blank titles and bodies in all ja/ko/en languages are required; incomplete contents return 422. A missing draft returns 404. Saving the published contents and deleting the draft occur in one transaction. Existing published contents are updated under the same identifier.")
  publishAnnouncement(@path announcementIdentifier: UuidV7): PublishAnnouncementResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @delete
  @route("/{announcementIdentifier}")
  @doc("Delete only the draft and all its language contents. Requires announcement:delete on announcement. Published contents are never deleted. Missing drafts succeed with an empty announcements array.")
  deleteAnnouncement(@path announcementIdentifier: UuidV7): DeleteAnnouncementResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}
