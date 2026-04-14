using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Current account state.")
model AccountSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account email address.")
  email: string;
  @doc("Account type.")
  type: string;
  @doc("Account display name.")
  name: string;
  @doc("Account status.")
  status: string;
}

@doc("Account creation result.")
model CreatedAccountSummary extends AccountSummary {
  @doc("Account category assigned during creation.")
  accountCategory: string;
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
  @doc("Identity group identifier that owns the permission.")
  identityGroupIdentifier: Uuid;
  @doc("Target account identifier.")
  targetAccountIdentifier: Uuid;
  @doc("Affiliation identifier covered by the permission.")
  affiliationIdentifier: Uuid;
  @doc("Timestamp when the permission was created.")
  createdAt: Timestamp;
}

@doc("Identity group snapshot returned after updates.")
model IdentityGroupSummary {
  @doc("Identity group identifier.")
  identityGroupIdentifier: Uuid;
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Identity group name.")
  name: string;
  @doc("Identity group role.")
  role: string;
  @doc("Whether this is the default identity group.")
  isDefault: boolean;
  @doc("Identity identifiers currently assigned to the group.")
  members?: Uuid[];
}

@doc("Identity group snapshot returned immediately after creation.")
model CreatedIdentityGroupSummary extends IdentityGroupSummary {
  @doc("Timestamp when the identity group was created.")
  createdAt: Timestamp;
}

@doc("Invitation state.")
model InvitationSummary {
  @doc("Invitation identifier.")
  invitationIdentifier: Uuid;
  @doc("Target account identifier.")
  accountIdentifier: Uuid;
  @doc("Identity identifier that created the invitation.")
  invitedByIdentityIdentifier: Uuid;
  @doc("Invitee email address.")
  email: string;
  @doc("Invitation token.")
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
