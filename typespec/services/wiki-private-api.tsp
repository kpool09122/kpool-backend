import "./wiki-private-api/common.tsp";
import "./wiki-private-api/wiki.tsp";
import "./wiki-private-api/image.tsp";
import "./wiki-private-api/principal.tsp";
import "./wiki-private-api/official-certification.tsp";
import "./wiki-private-api/video-link.tsp";

using TypeSpec.Http;

@service(#{ title: "k-pool Wiki Private API" })
@server("/api/wiki", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.WikiPrivateApi;
