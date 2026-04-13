using TypeSpec.Http;

@service(#{ title: "k-pool Wiki Private API" })
@server("/api/wiki", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.WikiPrivateApi;
