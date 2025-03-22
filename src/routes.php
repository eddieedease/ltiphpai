<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector;
use IMSGlobal\LTI\ToolProvider\ToolProvider;
use IMSGlobal\LTI\LTI_1p3_Tool;
use Firebase\JWT\JWT;

// Create database connection for LTI library
$db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']}",
    $config['db']['user'],
    $config['db']['password']
);
$dataConnector = DataConnector::getDataConnector('', $db);

// Add JWT handling
$container->set('jwt', function() {
    return new JWTHandler($config['jwt']['secret']);
});

// LTI 1.1 routes
$app->post('/lti/v1/launch', function (Request $request, Response $response) use ($dataConnector) {
    try {
        $tool = new ToolProvider($dataConnector);
        $tool->handleRequest();

        if (!$tool->ok) {
            throw new Exception("LTI launch failed: " . $tool->message);
        }

        // Create JWT token with launch data
        $token = $this->get('jwt')->createToken([
            'lti_user_id' => $tool->user->getId(),
            'lti_context_id' => $tool->context->getId(),
            'resource_link_id' => $tool->resourceLink->getId(),
            'is_instructor' => $tool->user->isStaff(),
            'lti_version' => '1.1'
        ]);

        // Return token and redirect URL
        return $response->withJson([
            'token' => $token,
            'redirect_url' => '/app'
        ]);

    } catch (Exception $e) {
        return $response->withJson(['error' => $e->getMessage()], 400);
    }
});

// LTI 1.3 routes
$app->get('/lti/v3/login', function (Request $request, Response $response) {
    try {
        $tool = new LTI_1p3_Tool(require(__DIR__ . '/../config/lti13.php'));
        
        // Handle OIDC login initiation
        $redirect = $tool->oidcLogin([
            'target_link_uri' => 'https://your-domain/lti/v3/launch',
            'login_hint' => $request->getQueryParams()['login_hint'] ?? ''
        ]);

        return $response
            ->withHeader('Location', $redirect)
            ->withStatus(302);

    } catch (Exception $e) {
        $response->getBody()->write("OIDC Login failed: " . $e->getMessage());
        return $response->withStatus(400);
    }
});

$app->post('/lti/v3/launch', function (Request $request, Response $response) {
    try {
        $tool = new LTI_1p3_Tool(require(__DIR__ . '/../config/lti13.php'));
        
        // Validate launch
        $launch = $tool->validateLaunch($request->getParsedBody());
        
        if (!$launch->isValid()) {
            throw new Exception("Invalid launch");
        }

        // Create JWT token with launch data
        $token = $this->get('jwt')->createToken([
            'lti_user_id' => $launch->getUserId(),
            'lti_context_id' => $launch->getContextId(),
            'resource_link_id' => $launch->getResourceLinkId(),
            'is_instructor' => $launch->hasRole('instructor'),
            'lti_version' => '1.3'
        ]);

        // Return token and redirect URL
        return $response->withJson([
            'token' => $token,
            'redirect_url' => '/app'
        ]);

    } catch (Exception $e) {
        return $response->withJson(['error' => $e->getMessage()], 400);
    }
});

$app->get('/lti/v3/keys', function (Request $request, Response $response) {
    try {
        $tool = new LTI_1p3_Tool(require(__DIR__ . '/../config/lti13.php'));
        
        // Return JWKS (JSON Web Key Set)
        $response->getBody()->write(json_encode([
            'keys' => [$tool->getPublicJwk()]
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (Exception $e) {
        $response->getBody()->write("JWKS endpoint failed: " . $e->getMessage());
        return $response->withStatus(400);
    }
});

// Grade submission endpoint
$app->post('/lti/grades', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    
    try {
        // Decode JWT token
        $token = $this->get('jwt')->decodeToken($data['token']);
        
        // Store result locally first
        $this->get('db')->executeQuery(
            "INSERT INTO lti_results (activity_id, user_id, score, result_data) 
             VALUES (?, ?, ?, ?)",
            [
                $token->activity_id,
                $token->lti_user_id,
                $data['score'],
                json_encode($data['extra_data'] ?? [])
            ]
        );

        // Send grade back to LMS
        if ($token->lti_version === '1.3') {
            // LTI 1.3 AGS
            $tool = new LTI_1p3_Tool(require(__DIR__ . '/../config/lti13.php'));
            $tool->submitScore([
                'userId' => $token->lti_user_id,
                'score' => $data['score'],
                'activity_progress' => 'Completed',
                'grading_progress' => 'FullyGraded'
            ]);
        } else {
            // LTI 1.1 Basic Outcomes
            $tool = new ToolProvider($dataConnector);
            $tool->resourceLink->id = $token->resource_link_id;
            $tool->user->id = $token->lti_user_id;
            $tool->updateOutcome($data['score']);
        }

        // Mark as sent in database
        $this->get('db')->executeQuery(
            "UPDATE lti_results SET is_sent = TRUE WHERE activity_id = ? AND user_id = ?",
            [$token->activity_id, $token->lti_user_id]
        );

        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});
