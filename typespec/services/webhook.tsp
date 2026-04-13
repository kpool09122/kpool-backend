using TypeSpec.Http;

@service(#{ title: "k-pool Webhook API" })
@server("/webhook", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.WebhookApi;

@doc("Response body returned when a Stripe webhook event is accepted for processing.")
model StripeWebhookAcceptedResponseBody {
  @doc("Webhook processing result.")
  status: "accepted";
}

@doc("Response body returned when a Stripe webhook event is ignored.")
model StripeWebhookIgnoredResponseBody {
  @doc("Webhook processing result.")
  status: "ignored";
}

@doc("Response body returned when Stripe signature verification fails.")
model StripeWebhookInvalidSignatureResponseBody {
  @doc("Error message describing why the webhook was rejected.")
  error: "Invalid signature";
}

@doc("200 response for accepted Stripe webhook events.")
model StripeWebhookAcceptedResponse {
  @statusCode statusCode: 200;
  @body body: StripeWebhookAcceptedResponseBody;
}

@doc("200 response for ignored Stripe webhook events.")
model StripeWebhookIgnoredResponse {
  @statusCode statusCode: 200;
  @body body: StripeWebhookIgnoredResponseBody;
}

@doc("400 response for Stripe webhook requests with an invalid signature.")
model StripeWebhookInvalidSignatureResponse {
  @statusCode statusCode: 400;
  @body body: StripeWebhookInvalidSignatureResponseBody;
}

@route("/stripe")
interface StripeWebhookOperations {
  @post
  @doc("Receive Stripe account external account webhook events.")
  receiveStripeWebhook(
    @header("Stripe-Signature") stripeSignature: string,
    @body body: unknown,
  ): StripeWebhookAcceptedResponse | StripeWebhookIgnoredResponse | StripeWebhookInvalidSignatureResponse;
}
