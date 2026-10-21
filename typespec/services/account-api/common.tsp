using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Current account state.")
model AccountDocumentUploadItem {
  @doc("Document type.")
  documentType: string;
  @doc("Base64-encoded document contents.")
  fileContents: string;
}

@doc("Request body for saving the complete account document set.")
model UploadAccountDocumentsRequestBody {
  @doc("Complete document set to save. An empty array removes all saved documents. Required documents are checked when requesting an account category change.")
  documents: AccountDocumentUploadItem[];
}

@doc("Uploaded account document state.")
model AccountDocumentSummary {
  @doc("Document type.")
  documentType: string;
  @doc("Stored document path.")
  documentPath: string;
  @doc("Timestamp when the document was uploaded.")
  uploadedAt: Timestamp;
}

@doc("Response body for account document upload.")
model UploadAccountDocumentsResponseBody {
  @doc("Uploaded documents.")
  documents: AccountDocumentSummary[];
}

@doc("Response body for account document list.")
model ListAccountDocumentsResponseBody {
  @doc("Saved documents.")
  documents: AccountDocumentSummary[];
}

model ContactAddressSummary {
  @doc("ISO 3166-1 alpha-2 country code.")
  countryCode?: string | null;
  @doc("Administrative area code for supported countries.")
  administrativeAreaCode?: string | null;
  @doc("Postal code.")
  postalCode?: string | null;
  @doc("Locality/city name.")
  locality?: string | null;
  @doc("Primary address line.")
  addressLine1?: string | null;
  @doc("Secondary address line.")
  addressLine2?: string | null;
}

model AccountSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account email address.")
  email: string;
  @doc("Account type. Null while initial setup is pending.")
  type: string | null;
  @doc("Account display name.")
  name: string;
  @doc("Account status.")
  status: string;
  @doc("Account category assigned during creation.")
  accountCategory: string;
  @doc("Contact phone number.")
  phone?: string | null;
  @doc("Contact address.")
  address?: ContactAddressSummary | null;
}

@doc("Affiliation contract terms.")
model AffiliationTermsSummary {
  @doc("Revenue share percentage.")
  revenueSharePercentage?: int32;
  @doc("Free-form contract notes.")
  contractNotes?: string;
}

@doc("Account summary attached to an affiliation.")
model AffiliationAccountSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account display name.")
  name: string;
  @doc("Account email address.")
  email: string;
}

@doc("Affiliation state.")
model AffiliationCommandSummary {
  @doc("Affiliation identifier.")
  affiliationIdentifier: Uuid;
  @doc("Agency account identifier.")
  agencyAccountIdentifier: Uuid;
  @doc("Talent account identifier.")
  talentAccountIdentifier: Uuid;
  @doc("Account that initiated the affiliation workflow.")
  requestedBy: Uuid;
  @doc("Affiliation status.")
  status: string;
  @doc("Optional affiliation terms.")
  terms?: AffiliationTermsSummary | null;
  @doc("Timestamp when the affiliation was requested.")
  requestedAt: Timestamp;
  @doc("Timestamp when the affiliation was activated.")
  activatedAt?: Timestamp | null;
  @doc("Timestamp when the affiliation was terminated.")
  terminatedAt?: Timestamp | null;
}

@doc("Affiliation state with related account summaries.")
model AffiliationSummary extends AffiliationCommandSummary {
  @doc("Agency account.")
  agencyAccount: AffiliationAccountSummary;
  @doc("Talent account.")
  talentAccount: AffiliationAccountSummary;
}

@doc("Response body for affiliation list.")
model ListAffiliationsResponseBody {
  @doc("Affiliations related to the authenticated account.")
  affiliations: AffiliationSummary[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of affiliations matching the filters.")
  total: int32;
  @doc("Number of items per page.")
  per_page: int32;
}

@doc("Response body for delegation list.")
model ListDelegationsResponseBody {
  @doc("Delegations related to the authenticated account.")
  delegations: DelegationSummary[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of delegations matching the filters.")
  total: int32;
  @doc("Number of items per page.")
  per_page: int32;
}

@doc("Principal group assigned to an account member.")
model MemberPrincipalGroupSummary {
  @doc("Principal group identifier.")
  principalGroupIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Description of the permissions granted by this group.")
  @maxLength(256)
  description?: string | null;
  @doc("Whether this is the default principal group.")
  isDefault: boolean;
}

@doc("Account member summary.")
model AccountMemberSummary {
  @doc("Principal lifecycle status. Stopped users remain in management lists and retain their permissions.")
  status: PrincipalStatus;
  @doc("Principal identifier.")
  principalIdentifier: Uuid;
  @doc("Identity identifier.")
  identityIdentifier: Uuid;
  @doc("Identity display name.")
  identityName: string;
  @doc("Identity email address.")
  email: string;
  @doc("Principal groups assigned to the member.")
  principalGroups: MemberPrincipalGroupSummary[];
}

@doc("Response body for account member list.")
model ListMembersResponseBody {
  @doc("Account members.")
  members: AccountMemberSummary[];
}

@doc("Response body for principal group list.")
model ListPrincipalGroupsResponseBody {
  @doc("Principal groups.")
  principalGroups: PrincipalGroupSummary[];
}

@doc("Principal group member summary.")
model PrincipalGroupMemberSummary {
  @doc("Principal identifier.")
  principalIdentifier: Uuid;
  @doc("Identity identifier.")
  identityIdentifier: Uuid;
  @doc("Identity display name.")
  identityName: string;
  @doc("Identity email address.")
  email: string;
}

@doc("Principal group snapshot returned after updates.")
model PrincipalGroupSummary {
  @doc("Principal group identifier.")
  principalGroupIdentifier: Uuid;
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Description of the permissions granted by this group.")
  @maxLength(256)
  description?: string | null;
  @doc("Role identifiers attached to the principal group.")
  roleIdentifiers: Uuid[];
  @doc("Whether this is the default principal group.")
  isDefault: boolean;
  @doc("Principal details currently assigned to the group.")
  members?: PrincipalGroupMemberSummary[];
}

@doc("Principal group snapshot returned immediately after creation.")
model CreatedPrincipalGroupSummary extends PrincipalGroupSummary {
  @doc("Timestamp when the principal group was created.")
  createdAt: Timestamp;
}

@doc("Invitation state.")
model InvitationSummary {
  @doc("Invitation identifier.")
  invitationIdentifier: Uuid;
  @doc("Target account identifier.")
  accountIdentifier: Uuid;
  @doc("Identity identifier that created the invitation.")
  invitedByPrincipalIdentifier: Uuid;
  @doc("Invitee email address.")
  email: string;
  @doc("One-time token.")
  token: string;
  @doc("Invitation status.")
  status: string;
  @doc("Invitation expiration timestamp.")
  expiresAt: Timestamp;
  @doc("Invitation creation timestamp.")
  createdAt: Timestamp;
}

@doc("Verification rejection reason.")
model VerificationRejectionReasonSummary {
  @doc("Reason code.")
  code: string;
  @doc("Optional reviewer detail.")
  detail?: string | null;
}

@doc("Account category change request state.")
model AccountCategoryChangeRequestSummary {
  @doc("Account category change request identifier.")
  requestIdentifier: Uuid;
  @doc("Target account identifier.")
  accountIdentifier: Uuid;
  @doc("Current account category.")
  currentAccountCategory: string;
  @doc("Requested account category.")
  requestedAccountCategory: string;
  @doc("Request status.")
  status: "pending" | "approved" | "rejected" | "withdrawn";
  @doc("Timestamp when the request was created.")
  requestedAt: Timestamp;
  @doc("Reviewer account identifier.")
  reviewedBy?: Uuid | null;
  @doc("Timestamp when the request was reviewed.")
  reviewedAt?: Timestamp | null;
  @doc("Optional rejection reason.")
  rejectionReason?: VerificationRejectionReasonSummary | null;
}

@doc("Account category change request state with target account summary.")
model AccountCategoryChangeRequestListItemSummary {
  ...AccountCategoryChangeRequestSummary;
  @doc("Account information captured when the request was submitted.")
  account: AccountSummary;
}

@doc("Response body for account category change request detail.")
model AccountCategoryChangeRequestDetailResponseBody {
  @doc("Account category change request.")
  request: AccountCategoryChangeRequestSummary;
  @doc("Account information captured when the request was submitted.")
  account: AccountSummary;
  @doc("Independent copies of the documents captured when the request was submitted.")
  documents: AccountDocumentSummary[];
}

@doc("Response body for account category change request list.")
model ListAccountCategoryChangeRequestsResponseBody {
  @doc("Account category change requests.")
  requests: AccountCategoryChangeRequestListItemSummary[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of account category change requests matching the filters.")
  total: int32;
  @doc("Number of items per page.")
  per_page: int32;
}
