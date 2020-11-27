<?php
use Firebase\JWT\JWT;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('name');
$log->pushHandler(new StreamHandler('logs/app.log', Logger::INFO));

function log_info($message, $data) {
  global $log;
  $log->info($message, $data);
}


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

  $accept = $app->request()->headers()->get('Accept');

  // bad conneg. if they mention json at all, send json.
  // this defaults to form encoded for clients that don't send a header for legacy client support.
  if(strpos($accept, 'application/json') !== false)
    $content_type = 'application/json';
  else
    $content_type = 'application/x-www-form-urlencoded';

  $app->response()->headers()->set('Content-Type', $content_type);

  $params = $req->params();


  $log_params = [
    'request' => $params
  ];

  if(isset($log_params['request']['code']))
    $log_params['request']['code'] = '*** '.strlen($log_params['request']['code']).' ***';

  if(isset($log_params['request']['code_verifier']))
    $log_params['request']['code_verifier'] = '*** '.strlen($log_params['request']['code_verifier']).' ***';

  if(array_key_exists('me', $params)) {
    $me = IndieAuth\Client::normalizeMeURL($params['me']);

    if(!$me) {
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
  } else {
    $authorizationEndpoint = 'https://indieauth.com/auth';
  }

  $log_params['Accept'] = $accept;
  $log_params['authorization_endpoint'] = $authorizationEndpoint;
  log_info('Token Request', $log_params);

  // Now verify the authorization code by querying the endpoint
  $auth = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, k($params, 'code'), k($params, 'me'), k($params, 'redirect_uri'), k($params, 'client_id'));

  if(array_key_exists('error', $auth)) {
    $app->response()->body(format_response($auth, $content_type));
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

