using TypeSpec.Http;

@service(#{ title: "k-pool Identity API" })
@server("/api/identity", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.IdentityApi;
