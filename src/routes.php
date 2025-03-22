<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// LTI 1.1 routes
$app->post('/lti/v1/launch', function (Request $request, Response $response) {
    // TODO: Implement LTI 1.1 launch
    $response->getBody()->write("LTI 1.1 Launch");
    return $response;
});

// LTI 1.3 routes
$app->get('/lti/v3/login', function (Request $request, Response $response) {
    // TODO: Implement OIDC login
    $response->getBody()->write("LTI 1.3 OIDC Login");
    return $response;
});

$app->post('/lti/v3/launch', function (Request $request, Response $response) {
    // TODO: Implement LTI 1.3 launch
    $response->getBody()->write("LTI 1.3 Launch");
    return $response;
});

$app->get('/lti/v3/keys', function (Request $request, Response $response) {
    // TODO: Implement JWKS endpoint
    $response->getBody()->write("LTI 1.3 Keys");
    return $response;
});
