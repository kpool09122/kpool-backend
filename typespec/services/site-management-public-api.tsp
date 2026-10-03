using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Site Management Public API" })
@server("/api/site-management", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.SiteManagementPublicApi;

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
  @doc("Identity identifier when the inquiry is associated with a signed-in user.")
  identityIdentifier?: Uuid | null;
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
  @doc("Identity identifier associated with the inquiry.")
  identityIdentifier?: Uuid | null;
  @doc("Category of the inquiry.")
  category: int32;
  @doc("Name of the person submitting the inquiry.")
  name: string;
  @doc("Identifiers of replies sent successfully for this inquiry.")
  replyIdentifiers: Uuid[];
  @doc("Timestamp when the inquiry was created.")
  createdAt: Timestamp;
}

@doc("Response containing contact inquiries associated with the signed-in identity.")
model ListMyContactsResponse {
  @statusCode statusCode: 200;
  @body body: MyContactSummary[];
}

@route("/my/contact")
interface MyContactOperations {
  @get
  @doc("List contact inquiries associated with the signed-in identity, newest first.")
  listMyContacts(): ListMyContactsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
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

@route("/contacts")
interface ContactListOperations {
  @get
  @doc("List all contact inquiries, optionally filtered by identity. Requires a site-management administrator.")
  listContacts(
    @query identityIdentifier?: Uuid | null,
    @doc("Reply-status filter: 1 for at least one successfully sent reply, 0 for none. When omitted (null), no reply-status filter is applied.")
    @query hasReply?: "0" | "1" | null,
    @doc("Maximum number of inquiries per page (1-100, default 50).")
    @query perPage?: int32 | null,
    @doc("Page number, starting at 1 (default 1).")
    @query page?: int32 | null,
  ): ListContactsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/contact")
interface ContactOperations {
  @post
  @route("/submit/v{version}")
  @doc("Submit a contact inquiry.")
  submitContact(
    @path version: int32,
    @body body: SubmitContactRequestBody,
  ): SubmitContactResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
