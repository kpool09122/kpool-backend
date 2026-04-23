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

@doc("Hero image payload for a draft wiki.")
model DraftWikiHeroImage {
  @doc("Image identifier shown as the hero image.")
  imageIdentifier: Uuid | null;
}

@doc("Detailed draft wiki payload used by the editor.")
model DraftWikiDetail {
  @doc("Draft wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number associated with the draft.")
  version: int32;
  @doc("Theme color applied to the wiki.")
  themeColor?: string;
  @doc("Hero image payload for the editor.")
  heroImage: DraftWikiHeroImage;
  @doc("Basic wiki content payload.")
  basic: unknown;
  @doc("Section content payloads.")
  sections: unknown[];
}

@doc("Summary of a published group linked from a talent draft wiki.")
model TalentDraftWikiGroupSummary {
  @doc("Published group wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the published group wiki.")
  slug: string;
  @doc("Language of the published group wiki.")
  language: string;
  @doc("Display name of the group.")
  name: string;
  @doc("Normalized name of the group.")
  normalizedName: string;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Group type.")
  groupType?: string;
  @doc("Current activity status of the group.")
  status?: string;
  @doc("Generation label of the group.")
  generation?: string;
  @doc("Debut date of the group.")
  debutDate?: plainDate;
  @doc("Disband date of the group.")
  disbandDate?: plainDate;
  @doc("Fandom name of the group.")
  fandomName: string;
  @doc("Official color palette of the group.")
  officialColors: string[];
  @doc("Emoji representing the group.")
  emoji: string;
  @doc("Representative symbol of the group.")
  representativeSymbol: string;
  @doc("Main image identifier of the group.")
  mainImageIdentifier?: Uuid;
}

@doc("Basic payload for a talent draft wiki.")
model TalentDraftWikiBasic {
  @doc("Display name of the talent.")
  name: string;
  @doc("Normalized name of the talent.")
  normalizedName: string;
  @doc("Real name of the talent.")
  realName: string;
  @doc("Normalized real name of the talent.")
  normalizedRealName: string;
  @doc("Birthday of the talent.")
  birthday?: plainDate;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Emoji representing the talent.")
  emoji: string;
  @doc("Representative symbol of the talent.")
  representativeSymbol: string;
  @doc("Position of the talent.")
  position: string;
  @doc("MBTI of the talent.")
  mbti?: string;
  @doc("Zodiac sign of the talent.")
  zodiacSign?: string;
  @doc("English proficiency level of the talent.")
  englishLevel?: string;
  @doc("Height of the talent in centimeters.")
  height?: int32;
  @doc("Blood type of the talent.")
  bloodType?: string;
  @doc("Fandom name of the talent.")
  fandomName: string;
  @doc("Profile image identifier of the talent.")
  profileImageIdentifier?: Uuid;
  @doc("Related published groups linked to the talent.")
  groups: TalentDraftWikiGroupSummary[];
}

@doc("Detailed talent draft wiki payload used by the editor.")
model TalentDraftWikiDetail {
  @doc("Draft wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number associated with the draft.")
  version: int32;
  @doc("Theme color applied to the wiki.")
  themeColor?: string;
  @doc("Hero image payload for the editor.")
  heroImage: DraftWikiHeroImage;
  @doc("Basic talent wiki content payload.")
  basic: TalentDraftWikiBasic;
  @doc("Section content payloads.")
  sections: unknown[];
}

@doc("Summary of a published group linked from a song draft wiki.")
model SongDraftWikiGroupSummary {
  @doc("Published group wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the published group wiki.")
  slug: string;
  @doc("Language of the published group wiki.")
  language: string;
  @doc("Display name of the group.")
  name: string;
  @doc("Normalized name of the group.")
  normalizedName: string;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Group type.")
  groupType?: string;
  @doc("Current activity status of the group.")
  status?: string;
  @doc("Generation label of the group.")
  generation?: string;
  @doc("Debut date of the group.")
  debutDate?: plainDate;
  @doc("Disband date of the group.")
  disbandDate?: plainDate;
  @doc("Fandom name of the group.")
  fandomName: string;
  @doc("Official color palette of the group.")
  officialColors: string[];
  @doc("Emoji representing the group.")
  emoji: string;
  @doc("Representative symbol of the group.")
  representativeSymbol: string;
  @doc("Main image identifier of the group.")
  mainImageIdentifier?: Uuid;
}

@doc("Summary of a published talent linked from a song draft wiki.")
model SongDraftWikiTalentSummary {
  @doc("Published talent wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the published talent wiki.")
  slug: string;
  @doc("Language of the published talent wiki.")
  language: string;
  @doc("Display name of the talent.")
  name: string;
  @doc("Normalized name of the talent.")
  normalizedName: string;
  @doc("Real name of the talent.")
  realName: string;
  @doc("Normalized real name of the talent.")
  normalizedRealName: string;
  @doc("Birthday of the talent.")
  birthday?: plainDate;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Emoji representing the talent.")
  emoji: string;
  @doc("Representative symbol of the talent.")
  representativeSymbol: string;
  @doc("Position of the talent.")
  position: string;
  @doc("MBTI of the talent.")
  mbti?: string;
  @doc("Zodiac sign of the talent.")
  zodiacSign?: string;
  @doc("English proficiency level of the talent.")
  englishLevel?: string;
  @doc("Height of the talent in centimeters.")
  height?: int32;
  @doc("Blood type of the talent.")
  bloodType?: string;
  @doc("Fandom name of the talent.")
  fandomName: string;
  @doc("Profile image identifier of the talent.")
  profileImageIdentifier?: Uuid;
}

@doc("Basic payload for a song draft wiki.")
model SongDraftWikiBasic {
  @doc("Display name of the song.")
  name: string;
  @doc("Normalized name of the song.")
  normalizedName: string;
  @doc("Song type.")
  songType: string;
  @doc("Genres associated with the song.")
  genres: string[];
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid;
  @doc("Release date of the song.")
  releaseDate?: plainDate;
  @doc("Album name.")
  albumName: string;
  @doc("Cover image identifier of the song.")
  coverImageIdentifier?: Uuid;
  @doc("Lyricist name.")
  lyricist: string;
  @doc("Normalized lyricist name.")
  normalizedLyricist: string;
  @doc("Composer name.")
  composer: string;
  @doc("Normalized composer name.")
  normalizedComposer: string;
  @doc("Arranger name.")
  arranger: string;
  @doc("Normalized arranger name.")
  normalizedArranger: string;
  @doc("Related published groups linked to the song.")
  groups: SongDraftWikiGroupSummary[];
  @doc("Related published talents linked to the song.")
  talents: SongDraftWikiTalentSummary[];
}

@doc("Detailed song draft wiki payload used by the editor.")
model SongDraftWikiDetail {
  @doc("Draft wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number associated with the draft.")
  version: int32;
  @doc("Theme color applied to the wiki.")
  themeColor?: string;
  @doc("Hero image payload for the editor.")
  heroImage: DraftWikiHeroImage;
  @doc("Basic song wiki content payload.")
  basic: SongDraftWikiBasic;
  @doc("Section content payloads.")
  sections: unknown[];
}

@doc("Basic payload for an agency draft wiki.")
model AgencyDraftWikiBasic {
  @doc("Display name of the agency.")
  name: string;
  @doc("Normalized name of the agency.")
  normalizedName: string;
  @doc("Chief executive officer of the agency.")
  ceo: string;
  @doc("Normalized chief executive officer name.")
  normalizedCeo: string;
  @doc("Date when the agency was founded.")
  foundedIn?: plainDate;
  @doc("Parent agency identifier.")
  parentAgencyIdentifier?: Uuid;
  @doc("Current activity status of the agency.")
  status?: string;
  @doc("Logo image identifier of the agency.")
  logoImageIdentifier?: Uuid;
  @doc("Official website of the agency.")
  officialWebsite: string;
  @doc("Social links of the agency.")
  socialLinks: string[];
}

@doc("Detailed agency draft wiki payload used by the editor.")
model AgencyDraftWikiDetail {
  @doc("Draft wiki identifier.")
  wikiIdentifier: Uuid;
  @doc("Unique slug of the wiki.")
  slug: string;
  @doc("Language of the wiki.")
  language: string;
  @doc("Resource type that the wiki belongs to.")
  resourceType: string;
  @doc("Published version number associated with the draft.")
  version: int32;
  @doc("Theme color applied to the wiki.")
  themeColor?: string;
  @doc("Hero image payload for the editor.")
  heroImage: DraftWikiHeroImage;
  @doc("Basic agency wiki content payload.")
  basic: AgencyDraftWikiBasic;
  @doc("Section content payloads.")
  sections: unknown[];
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
