using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiPrivateApi;

@doc("Request body for creating an official certification request.")
model RequestCertificationRequestBody {
  @doc("Resource type that the certification belongs to.")
  resourceType: string;
  @doc("Translation set identifier to certify.")
  translationSetIdentifier: Uuid;
}

@doc("Response returned when an official certification request is created.")
model CreateOfficialCertificationResponse {
  @statusCode statusCode: 201;
  @body body: OfficialCertificationSummary;
}

@doc("Response returned when an official certification operation succeeds.")
model OkOfficialCertificationResponse {
  @statusCode statusCode: 200;
  @body body: OfficialCertificationSummary;
}

@doc("Owner account summary shown in the operator official certification request list.")
model OfficialCertificationOwnerAccount {
  @doc("Owner account identifier.")
  accountIdentifier: Uuid;
  @doc("Owner account email address.")
  email: string;
  @doc("Owner account type.")
  type: string;
  @doc("Owner account display name.")
  name: string;
  @doc("Owner account status.")
  status: string;
  @doc("Owner account category.")
  category: string;
}

@doc("Summary shown in the operator official certification request list.")
model OfficialCertificationListItem {
  @doc("Certification identifier.")
  certificationIdentifier: Uuid;
  @doc("Resource type that the certification belongs to.")
  resourceType: string;
  @doc("Translation set identifier related to the certification.")
  translationSetIdentifier: Uuid;
  @doc("Account that owns the certified resource.")
  ownerAccount: OfficialCertificationOwnerAccount | null;
  @doc("Published wikis related by the same translation set identifier.")
  wikis: WikiListItem[];
  @doc("Current workflow status of the certification.")
  status: string;
  @doc("Date and time when the certification was requested.")
  requestedAt: utcDateTime;
  @doc("Date and time when the certification was approved.")
  approvedAt: utcDateTime | null;
  @doc("Date and time when the certification was rejected.")
  rejectedAt: utcDateTime | null;
}

@doc("Response returned when listing official certification requests.")
model ListOfficialCertificationsResponse {
  @statusCode statusCode: 200;
  @body body: {
    officialCertifications: OfficialCertificationListItem[];
    current_page: int32;
    last_page: int32;
    total: int32;
    per_page: int32;
  };
}

@route("/official-certifications")
interface OfficialCertificationListOperations {
  @get
  @doc("List official certification requests for operators.")
  listOfficialCertifications(
    @query status?: string | null,
    @query perPage?: int32 | null,
  ): ListOfficialCertificationsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/official-certification")
interface OfficialCertificationOperations {
  @post
  @route("/request")
  @doc("Create an official certification request.")
  requestCertification(
    @body body: RequestCertificationRequestBody,
  ): CreateOfficialCertificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{certificationId}/approve")
  @doc("Approve an official certification request.")
  approveCertification(
    @path certificationId: Uuid,
  ): OkOfficialCertificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{certificationId}/reject")
  @doc("Reject an official certification request.")
  rejectCertification(
    @path certificationId: Uuid,
  ): OkOfficialCertificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
