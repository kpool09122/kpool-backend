using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Site Management API" })
@server("/api/site-management", "Laravel route prefix from bootstrap/app.php.")
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
  @post
  @route("/{contactIdentifier}/replies")
  @doc("Send a reply to a contact inquiry. Requires a site-management administrator. Returns no payload; mail delivery failures retain failed reply history and return 500.")
  replyContact(
    @doc("UUIDv7 contact identifier, matching the existing ContactIdentifier domain constraint.")
    @path contactIdentifier: UuidV7,
    @body body: ReplyContactRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @doc("List all contact inquiries, optionally filtered by principal. Requires a site-management administrator.")
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
  @doc("Get the specified contact inquiry for an principal. Requires a site-management administrator.")
  getContactDetail(
    @path principalIdentifier: Uuid,
    @path contactIdentifier: Uuid,
  ): ContactDetail | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;


  @get
  @route("/principals/{principalIdentifier}")
  @doc("List contact inquiries for the specified principal. Requires a site-management administrator.")
  listContactsByPrincipal(
    @path principalIdentifier: Uuid,
  ): ListContactsByPrincipalResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/submit/v{version}")
  @doc("Submit a contact inquiry.")
  submitContact(
    @path version: int32,
    @body body: SubmitContactRequestBody,
  ): SubmitContactResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
