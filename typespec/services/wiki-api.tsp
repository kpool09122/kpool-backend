import "./wiki-api/common.tsp";
import "./wiki-api/wiki.tsp";
import "./wiki-api/image.tsp";
import "./wiki-api/principal.tsp";
import "./wiki-api/official-certification.tsp";
import "./wiki-api/video-link.tsp";

using TypeSpec.Http;

@service(#{ title: "k-pool Wiki API" })
@server("/api/v1/wiki", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.WikiApi;
