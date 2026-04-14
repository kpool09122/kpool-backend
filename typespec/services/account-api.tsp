import "./account-api/common.tsp";
import "./account-api/account.tsp";
import "./account-api/delegation.tsp";
import "./account-api/delegation-permission.tsp";
import "./account-api/identity-group.tsp";
import "./account-api/invitation.tsp";
import "./account-api/account-verification.tsp";
import "./account-api/affiliation.tsp";

using TypeSpec.Http;

@service(#{ title: "k-pool Account API" })
@server("/api/account", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.AccountApi;
