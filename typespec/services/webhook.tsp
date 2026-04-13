using TypeSpec.Http;

@service(#{ title: "k-pool Webhook API" })
@server("/webhook", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.WebhookApi;
