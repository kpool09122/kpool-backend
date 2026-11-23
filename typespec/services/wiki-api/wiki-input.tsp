using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Agency basic input. Only name is required on creation; omitted fields use existing API defaults. For editing, retain all current values.")
model AgencyWikiBasicInput {
  @doc("Display name of the agency.")
  name: string;
  @doc("Normalized name of the agency.")
  normalizedName?: string;
  @doc("Chief executive officer of the agency.")
  ceo?: string;
  @doc("Normalized chief executive officer name.")
  normalizedCeo?: string;
  @doc("Date when the agency was founded.")
  foundedIn?: plainDate | null;
  @doc("Parent agency identifier.")
  parentAgencyIdentifier?: Uuid | null;
  @doc("Current activity status of the agency.")
  status?: string | null;
  @doc("Official website of the agency.")
  officialWebsite?: string | null;
  @doc("Social links of the agency.")
  socialLinks?: string[];
}

@doc("Group basic input. Only name is required on creation; omitted fields use existing API defaults. For editing, retain all current values.")
model GroupWikiBasicInput {
  @doc("Display name of the group.")
  name: string;
  @doc("Normalized name of the group.")
  normalizedName?: string;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid | null;
  @doc("Group type.")
  groupType?: "boy_group" | "girl_group" | "co_ed" | null;
  @doc("Current activity status of the group.")
  status?: string | null;
  @doc("Generation label of the group.")
  generation?: string | null;
  @doc("Debut date of the group.")
  debutDate?: plainDate | null;
  @doc("Disband date of the group.")
  disbandDate?: plainDate | null;
  @doc("Fandom name of the group.")
  fandomName?: string;
  @doc("Official colors of the group.")
  officialColors?: OfficialColor[];
  @doc("Representative emoji of the group.")
  emoji?: string;
  @doc("Representative symbol of the group.")
  representativeSymbol?: string;
}

@doc("Talent basic input. Only name is required on creation; omitted fields use existing API defaults. For editing, retain all current values.")
model TalentWikiBasicInput {
  @doc("Display name of the talent.")
  name: string;
  @doc("Normalized name of the talent.")
  normalizedName?: string;
  @doc("Real name of the talent.")
  realName?: string;
  @doc("Normalized real name of the talent.")
  normalizedRealName?: string;
  @doc("Birthday of the talent.")
  birthday?: plainDate | null;
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid | null;
  @doc("Emoji representing the talent.")
  emoji?: string;
  @doc("Representative symbol of the talent.")
  representativeSymbol?: string;
  @doc("Position of the talent.")
  position?: string;
  @doc("MBTI of the talent.")
  mbti?: "INTJ" | "INTP" | "ENTJ" | "ENTP" | "INFJ" | "INFP" | "ENFJ" | "ENFP" | "ISTJ" | "ISFJ" | "ESTJ" | "ESFJ" | "ISTP" | "ISFP" | "ESTP" | "ESFP" | null;
  @doc("Zodiac sign of the talent.")
  zodiacSign?: "aries" | "taurus" | "gemini" | "cancer" | "leo" | "virgo" | "libra" | "scorpio" | "sagittarius" | "capricorn" | "aquarius" | "pisces" | null;
  @doc("English proficiency level of the talent.")
  englishLevel?: "native" | "fluent" | "conversational" | "basic" | "none" | null;
  @doc("Height of the talent in centimeters.")
  height?: int32 | string | null;
  @doc("Blood type of the talent.")
  bloodType?: "A" | "B" | "O" | "AB" | null;
  @doc("Fandom name of the talent.")
  fandomName?: string;

  @doc("Published group wiki identifiers; map groups[].wikiIdentifier from detail responses.")
  groupIdentifiers?: Uuid[];
}

@doc("Song basic input. Only name is required on creation; omitted fields use existing API defaults. For editing, retain all current values.")
model SongWikiBasicInput {
  @doc("Display name of the song.")
  name: string;
  @doc("Normalized name of the song.")
  normalizedName?: string;
  @doc("Song type.")
  songType?: "title_track" | "b_side" | "ost" | "solo" | "collaboration" | "pre_release" | null;
  @doc("Genres associated with the song.")
  genres?: string[];
  @doc("Associated agency identifier.")
  agencyIdentifier?: Uuid | null;
  @doc("Release date of the song.")
  releaseDate?: plainDate | null;
  @doc("Album name.")
  albumName?: string | null;
  @doc("Lyricist name.")
  lyricist?: string;
  @doc("Normalized lyricist name.")
  normalizedLyricist?: string;
  @doc("Composer name.")
  composer?: string;
  @doc("Normalized composer name.")
  normalizedComposer?: string;
  @doc("Arranger name.")
  arranger?: string;
  @doc("Normalized arranger name.")
  normalizedArranger?: string;

  @doc("Published group wiki identifiers; map groups[].wikiIdentifier from detail responses.")
  groupIdentifiers?: Uuid[];
  @doc("Published talent wiki identifiers; map talents[].wikiIdentifier from detail responses.")
  talentIdentifiers?: Uuid[];
}

@doc("Choose the basic shape matching resourceType. Related summaries in GET responses must be converted to identifier arrays.")
union WikiBasicInput {
  AgencyWikiBasicInput,
  GroupWikiBasicInput,
  TalentWikiBasicInput,
  SongWikiBasicInput,
}

@doc("Content node. Use type=section with title/contents for a section, or a block type with its corresponding fields. contents contains nodes of this same shape; nested nodes remain arbitrary JSON to avoid a depth limit. Preserve existing nodes and displayOrder when editing. The existing API validates block-specific rules.")
model WikiContentInput {
  type: "section" | "text" | "image" | "image_gallery" | "embed" | "quote" | "list" | "history" | "google_trends" | "table" | "profile_card_list";
  @doc("Stable section key, preserved through editing and translation. Optional for legacy content.")
  sectionKey?: string | null;
  @doc("Stable block key, preserved through editing and translation. Optional for legacy content.")
  blockKey?: string | null;
  displayOrder?: int32;
  title?: string | null;
  contents?: unknown[];
  content?: string;
  imageIdentifier?: Uuid | null;
  imageIdentifiers?: Uuid[];
  imageWidth?: int32 | null;
  caption?: string | null;
  alt?: string | null;
  provider?: string | null;
  embedId?: string;
  source?: string | null;
  listType?: "bullet" | "numbered";
  items?: string[];
  entries?: WikiHistoryEntryInput[];
  @doc("Required for google_trends. A non-blank search term (maximum 100 characters). Defaults on generation/translation to the displayed Wiki basic name, without related names.")
  @minLength(1)
  @maxLength(100)
  searchTerm?: string;
  @doc("Required for google_trends. Searcher location, not search language: JP=Japan, KR=Korea, all=worldwide. Send all for worldwide because HTTP middleware converts empty strings to null. Stored and returned worldwide regions remain empty strings. Generation/translation defaults: ja=JP, ko=KR, en=worldwide.")
  region?: "JP" | "KR" | "all";
  @doc("Required for google_trends. Supported periods: past 3 months, 6 months, 1 year (generation default), 2 years, 5 years, and 10 years. Preserved on translation. Only structured configuration is accepted, not HTML or JavaScript.")
  timeRange?: "today 3-m" | "today 6-m" | "today 12-m" | "today 2-y" | "today 5-y" | "today 10-y";
  headerCells?: string[] | null;
  rowCells?: string[][];
  tableWidth?: string | null;
  wikiIdentifiers?: Uuid[];
  cardShape?: string | null;
}

model WikiHistoryEntryInput {
  year: int32;
  title: string;
  detail?: string | null;
}
