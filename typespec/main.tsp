import "@typespec/http";
import "@typespec/rest";
import "@typespec/openapi3";

import "./common/core.tsp";
import "./common/auth.tsp";
import "./common/problem-details.tsp";
import "./services/account-api.tsp";
import "./services/identity-api.tsp";
import "./services/monetization-api.tsp";
import "./services/site-management-api.tsp";
import "./services/webhook.tsp";
import "./services/wiki-api.tsp";
