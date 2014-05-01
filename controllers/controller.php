<?php

$app->get('/', function() use($app) {
  $app->response()->body('<h1>IndieAuth Token Endpoint</h1><p><a href="https://github.com/aaronpk/IndieAuth-Token-Endpoint">Source Code</a></p>');
});

// Exchange an authorization code for an access token
$app->post('/token', function() use($app) {

  $req = $app->request();
  $app->response()->headers()->set('Content-Type', 'application/x-www-form-urlencoded');

  $params = $req->params();
  
  // the "me" parameter is user input, and may be in a couple of different forms:
  // aaronparecki.com http://aaronparecki.com http://aaronparecki.com/
  // Normlize the value now (move this into a function in IndieAuth\Client later)
  if(!array_key_exists('me', $params) || !($me = normalizeMeURL($params['me']))) {
    $app->response()->body(http_build_query(array(
      'error' => 'invalid_parameter',
      'error_description' => 'The "me" parameter provided was not valid'
    )));
    return;
  }

  // Try to discover the authorization endpoint for this user
  $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);

  if(!$authorizationEndpoint) {
    $app->response()->body(http_build_query(array(
      'error' => 'missing_authorization_endpoint',
      'error_description' => 'No authorization endpoint was discovered'
    )));
    return;
  }

  // Now verify the authorization code by querying the endpoint
  $auth = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $params['code'], $params['me'], $params['redirect_uri'], $params['client_id'], $params['state']);

  if(array_key_exists('error', $auth)) {
    $app->response()->body(http_build_query($auth));
  } else {
    // Token is valid!
    // $auth['me']
    // $auth['scope']

    $token_data = array(
      'date_issued' => date('Y-m-d H:i:s'),
      'me' => $auth['me'],
      'client_id' => $params['client_id'],
      'scope' => array_key_exists('scope', $auth) ? $auth['scope'] : '',
      'nonce' => mt_rand(1000000,pow(2,30))
    );
    $token = JWT::encode($token_data, Config::$jwtKey);

    $app->response()->body(http_build_query(array(
      'me' => $auth['me'],
      'scope' => $token_data['scope'],
      'access_token' => $token
    )));
  }
});

// Used by the Micropub endpoint to verify an access token
$app->get('/token', function() use($app) {
  $req = $app->request();
  $app->response()->headers()->set('Content-Type', 'application/x-www-form-urlencoded');

  $tokenString = false;
  $error_description = false;
  
  $authHeader = $app->request()->headers()->get('Authorization');
  if(preg_match('/Bearer (.+)/', $authHeader, $match)) {
    $tokenString = $match[1];
  }

  if($tokenString) {
    try {
      $token = JWT::decode($tokenString, Config::$jwtKey);
    } catch(Exception $e) {
      $token = false;
      $error_description = 'The token provided was malformed';
    }

    if($token) {
      $app->response()->body(http_build_query(array(
        'me' => $token->me,
        'scope' => $token->scope,
        'client_id' => $token->client_id,
        'issued' => strtotime($token->date_issued),
      )));
      return;
    }
  }

  $app->response()->setStatus(400);
  $app->response()->headers()->set('Content-Type', 'application/x-www-form-urlencoded');
  $app->response()->body(http_build_query(array(
    'error' => 'unauthorized',
    'error_description' => ($error_description ?: 'An access token is required. Send an HTTP Authorization header such as \'Authorization: Bearer xxxxxx\'')
  )));
});

