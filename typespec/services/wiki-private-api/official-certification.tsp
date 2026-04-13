using TypeSpec.Http;

namespace KPool.WikiPrivateApi;

@doc("Request body for creating an official certification request.")
model RequestCertificationRequestBody {
  @doc("Resource type that the certification belongs to.")
  resourceType: string;
  @doc("Wiki identifier to certify.")
  wikiId: Uuid;
  @doc("Owner account identifier for the certification.")
  ownerAccountId: Uuid;
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

@route("/official-certification")
interface OfficialCertificationOperations {
  @post
  @route("/request")
  @doc("Create an official certification request.")
  requestCertification(
    @body body: RequestCertificationRequestBody,
  ): CreateOfficialCertificationResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{certificationId}/approve")
  @doc("Approve an official certification request.")
  approveCertification(
    @path certificationId: Uuid,
  ): OkOfficialCertificationResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{certificationId}/reject")
  @doc("Reject an official certification request.")
  rejectCertification(
    @path certificationId: Uuid,
  ): OkOfficialCertificationResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
