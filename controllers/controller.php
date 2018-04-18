<?php
use Firebase\JWT\JWT;

function format_response($data, $content_type) {
  if($content_type == 'application/json')
    return json_encode($data);
  else
    return http_build_query($data);  
}

$app->get('/', function($format='html') use($app) {
  $res = $app->response();

  $html = render('index', [
    'title' => 'IndieAuth Token Endpoint'
  ]);
  $res->body($html);
});

// Exchange an authorization code for an access token
$app->post('/token', function() use($app) {

  $req = $app->request();

  $content_type = 'application/x-www-form-urlencoded';
  if($app->request()->headers()->get('Accept') == 'application/json') {
    $content_type = 'application/json';
  }
  $app->response()->headers()->set('Content-Type', $content_type);

  $params = $req->params();
  
  // the "me" parameter is user input, and may be in a couple of different forms:
  // aaronparecki.com http://aaronparecki.com http://aaronparecki.com/
  // Normlize the value now (move this into a function in IndieAuth\Client later)
  if(!array_key_exists('me', $params) || !($me = IndieAuth\Client::normalizeMeURL($params['me']))) {
    $app->response()->body(format_response([
      'error' => 'invalid_parameter',
      'error_description' => 'The "me" parameter provided was not valid'
    ], $content_type));
    return;
  }

  // Try to discover the authorization endpoint for this user
  $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);

  if(!$authorizationEndpoint) {
    $app->response()->body(format_response([
      'error' => 'missing_authorization_endpoint',
      'error_description' => 'No authorization endpoint was discovered'
    ], $content_type));
    return;
  }

  // Now verify the authorization code by querying the endpoint
  $auth = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, k($params, 'code'), k($params, 'me'), k($params, 'redirect_uri'), k($params, 'client_id'));

  if(array_key_exists('error', $auth)) {
    $app->response()->body(http_build_query($auth));
  } elseif(array_key_exists('me', $auth)) {
    // Token is valid!
    // $auth['me']
    // $auth['scope']

    $token_data = [
      'me' => $auth['me'],
      'issued_by' => 'https://' . Config::$hostname . '/token',
      'client_id' => $params['client_id'],
      'issued_at' => time(),
      'scope' => k($auth, 'scope', ''),
      'nonce' => mt_rand(1000000,pow(2,30))
    ];
    $token = JWT::encode($token_data, Config::$jwtKey);

    $app->response()->body(format_response([
      'me' => $auth['me'],
      'scope' => $token_data['scope'],
      'access_token' => $token,
      'token_type' => 'Bearer'
    ], $content_type));
  } else {
    $app->response()->body(format_response([
      'error' => 'unknown_error',
      'error_description' => 'There was an unknown error verifying the authorization code.',
      'auth_response' => $auth
    ], $content_type));
  }
});

// Used by the Micropub endpoint to verify an access token
$app->get('/token', function() use($app) {
  $req = $app->request();

  // Return a regular HTML page if no token was sent
  if($app->request()->headers()->get('Authorization') == '') {
    $html = render('index', [
      'title' => 'IndieAuth Token Endpoint'
    ]);
    $app->response()->body($html);
    return;
  }

  $content_type = 'application/x-www-form-urlencoded';
  if($app->request()->headers()->get('Accept') == 'application/json') {
    $content_type = 'application/json';
  }
  $app->response()->headers()->set('Content-Type', $content_type);

  $tokenString = false;
  $error_description = false;
  
  $authHeader = $app->request()->headers()->get('Authorization');
  if(preg_match('/Bearer (.+)/', $authHeader, $match)) {
    $tokenString = $match[1];
  }

  if($tokenString) {
    try {
      $token = JWT::decode($tokenString, Config::$jwtKey, ['HS256']);
    } catch(Exception $e) {
      $token = false;
      $error_description = 'The token provided was malformed';
    }

    if($token) {
      $app->response()->body(format_response($token, $content_type));
      return;
    }
  }

  $app->response()->setStatus(400);
  $error = [
    'error' => 'unauthorized',
    'error_description' => ($error_description ?: 'An access token is required. Send an HTTP Authorization header such as \'Authorization: Bearer xxxxxx\'')
  ];
  $app->response()->body(format_response($error, $content_type));
});

