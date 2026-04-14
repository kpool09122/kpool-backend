using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiPrivateApi;

@doc("Association targets that link a wiki to related agencies, groups, or talents.")
model WikiAssociationTargets {
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Associated group identifiers.")
  groupIdentifiers?: Uuid[];
  @doc("Associated talent identifiers.")
  talentIdentifiers?: Uuid[];
}

@doc("Summary of a draft wiki.")
model DraftWikiSummary {
  @doc("Language of the wiki.")
  language: string;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Current workflow status of the draft wiki.")
  status: string;
}

@doc("Summary of a published wiki.")
model PublishedWikiSummary {
  @doc("Language of the wiki.")
  language: string;
  @doc("Display name of the wiki.")
  name: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number.")
  version: int32;
}

@doc("Summary of a draft image.")
model ImageDraftSummary {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Usage category of the image.")
  imageUsage: string;
  @doc("Current workflow status of the image.")
  status: string;
}

@doc("Summary of an image.")
model ImageSummary {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Usage category of the image.")
  imageUsage: string;
  @doc("Whether the image is currently hidden.")
  isHidden: boolean;
}

@doc("Summary of an image hide request.")
model ImageHideRequestSummary {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Name of the requester.")
  requesterName: string;
  @doc("Email address of the requester.")
  requesterEmail: string;
  @doc("Reason for requesting the image to be hidden.")
  reason: string;
  @doc("Current review status of the hide request.")
  status: string;
}

@doc("Summary of an image hide request review result.")
model ImageHideReviewSummary {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Review status of the hide request.")
  status: string;
  @doc("Comment left by the reviewer.")
  reviewerComment: string;
  @doc("Whether the image is hidden after the review.")
  isHidden: boolean;
}

@doc("Summary of a principal.")
model PrincipalSummary {
  @doc("Principal identifier.")
  principalIdentifier: Uuid;
  @doc("Identity identifier linked to the principal.")
  identityIdentifier: Uuid;
  @doc("Whether the principal is delegated.")
  isDelegatedPrincipal: boolean;
  @doc("Whether the principal is enabled.")
  isEnabled: boolean;
}

@doc("Summary of a principal group.")
model PrincipalGroupSummary {
  @doc("Principal group identifier.")
  principalGroupIdentifier: Uuid;
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Whether this is the default principal group.")
  isDefault: boolean;
  @doc("Number of members in the principal group.")
  memberCount: int32;
  @doc("Timestamp when the principal group was created.")
  createdAt: string;
}

@doc("Summary of a role.")
model RoleSummary {
  @doc("Role identifier.")
  roleIdentifier: Uuid;
  @doc("Role name.")
  name: string;
  @doc("Whether this is a system role.")
  isSystemRole: boolean;
  @doc("Timestamp when the role was created.")
  createdAt: string;
}

@doc("Summary of a policy.")
model PolicySummary {
  @doc("Policy identifier.")
  policyIdentifier: Uuid;
  @doc("Policy name.")
  name: string;
  @doc("Whether this is a system policy.")
  isSystemPolicy: boolean;
  @doc("Timestamp when the policy was created.")
  createdAt: string;
}

@doc("Summary of an official certification request.")
model OfficialCertificationSummary {
  @doc("Certification identifier.")
  certificationIdentifier: Uuid;
  @doc("Resource type that the certification belongs to.")
  resourceType: string;
  @doc("Wiki identifier related to the certification.")
  wikiIdentifier: Uuid;
  @doc("Current workflow status of the certification.")
  status: string;
}

@doc("Single condition clause in a policy.")
model PolicyConditionClause {
  @doc("Field name to evaluate.")
  field: string;
  @doc("Operator used for the comparison.")
  operator: string;
  @doc("Value to compare against.")
  value: string;
}

@doc("Conditional constraints for a policy statement.")
model PolicyCondition {
  @doc("List of condition clauses.")
  clauses?: PolicyConditionClause[];
}

@doc("Statement that grants or denies actions on resource types.")
model PolicyStatement {
  @doc("Whether the statement allows or denies the action.")
  effect: string;
  @doc("Actions covered by this statement.")
  actions: string[];
  @doc("Resource types covered by this statement.")
  resourceTypes: string[];
  @doc("Optional condition attached to the statement.")
  condition?: PolicyCondition;
}
