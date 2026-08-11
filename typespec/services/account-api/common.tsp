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

@doc("Request body for account document upload.")
model UploadAccountDocumentsRequestBody {
  @doc("Documents to upload.")
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

model AccountSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account email address.")
  email: string;
  @doc("Account category.")
  type: string;
  @doc("Account display name.")
  name: string;
  @doc("Account status.")
  status: string;
  @doc("Account category assigned during creation.")
  accountCategory: string;
}

@doc("Account creation request result. Empty when the request is accepted without exposing whether an account already exists.")
model CreateAccountResult {
  @doc("Account identifier when a new account was created.")
  accountIdentifier?: Uuid;
  @doc("Account email address when a new account was created.")
  email?: string;
  @doc("Account category when a new account was created.")
  type?: string;
  @doc("Account display name when a new account was created.")
  name?: string;
  @doc("Account status when a new account was created.")
  status?: string;
  @doc("Account category assigned during creation.")
  accountCategory?: string;
}

@doc("Affiliation contract terms.")
model AffiliationTermsSummary {
  @doc("Revenue share percentage.")
  revenueSharePercentage?: int32;
  @doc("Free-form contract notes.")
  contractNotes?: string;
}

@doc("Affiliation state.")
model AffiliationSummary {
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

@doc("Delegation state.")
model DelegationSummary {
  @doc("Delegation identifier.")
  delegationIdentifier: Uuid;
  @doc("Affiliation identifier backing the delegation.")
  affiliationIdentifier: Uuid;
  @doc("Delegate identity identifier.")
  delegateIdentifier: Uuid;
  @doc("Delegator identity identifier.")
  delegatorIdentifier: Uuid;
  @doc("Delegation status.")
  status: string;
  @doc("Delegation direction.")
  direction: string;
  @doc("Timestamp when the delegation was requested.")
  requestedAt: Timestamp;
  @doc("Timestamp when the delegation was approved.")
  approvedAt?: Timestamp | null;
  @doc("Timestamp when the delegation was revoked.")
  revokedAt?: Timestamp | null;
}

@doc("Delegation permission state.")
model DelegationPermissionSummary {
  @doc("Delegation permission identifier.")
  delegationPermissionIdentifier: Uuid;
  @doc("Principal group identifier that owns the permission.")
  principalGroupIdentifier: Uuid;
  @doc("Target account identifier.")
  targetAccountIdentifier: Uuid;
  @doc("Affiliation identifier covered by the permission.")
  affiliationIdentifier: Uuid;
  @doc("Timestamp when the permission was created.")
  createdAt: Timestamp;
}

@doc("Principal group assigned to an account member.")
model MemberPrincipalGroupSummary {
  @doc("Principal group identifier.")
  principalGroupIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Whether this is the default principal group.")
  isDefault: boolean;
}

@doc("Account member summary.")
model AccountMemberSummary {
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

@doc("Verification document state.")
model VerificationDocumentSummary {
  @doc("Document identifier.")
  documentIdentifier: Uuid;
  @doc("Document type.")
  documentType: string;
  @doc("Stored document path.")
  documentPath: string;
  @doc("Original file name.")
  originalFileName: string;
  @doc("Document size in bytes.")
  fileSizeBytes: int32;
  @doc("Timestamp when the document was uploaded.")
  uploadedAt: Timestamp;
}

@doc("Account verification state.")
model AccountVerificationSummary {
  @doc("Verification identifier.")
  verificationIdentifier: Uuid;
  @doc("Account identifier under review.")
  accountIdentifier: Uuid;
  @doc("Verification type.")
  verificationType: string;
  @doc("Verification status.")
  status: string;
  @doc("Applicant full name.")
  applicantName: string;
  @doc("Timestamp when the verification was requested.")
  requestedAt: Timestamp;
  @doc("Reviewer account identifier.")
  reviewedBy?: Uuid | null;
  @doc("Timestamp when the verification was reviewed.")
  reviewedAt?: Timestamp | null;
  @doc("Optional rejection reason.")
  rejectionReason?: VerificationRejectionReasonSummary | null;
  @doc("Submitted documents.")
  documents: VerificationDocumentSummary[];
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
  status: string;
  @doc("Timestamp when the request was created.")
  requestedAt: Timestamp;
  @doc("Reviewer account identifier.")
  reviewedBy?: Uuid | null;
  @doc("Timestamp when the request was reviewed.")
  reviewedAt?: Timestamp | null;
  @doc("Optional rejection reason.")
  rejectionReason?: VerificationRejectionReasonSummary | null;
}
