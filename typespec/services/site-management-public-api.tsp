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
