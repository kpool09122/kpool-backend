using TypeSpec.Http;

@service(#{ title: "k-pool Account API" })
@server("/api/account", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.AccountApi;
