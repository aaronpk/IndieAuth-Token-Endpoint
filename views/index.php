<div class="container narrow">

  <div class="jumbotron home">
    <img src="/images/indieauth-logo-color.png" class="pull-left">
    <h1>Token Endpoint</h1>
    <p>This is an IndieAuth Token Endpoint that you can use to jump-start the development of your Micropub endpoint.</p>
  </div>



  <h2 id="use-it"><a href="#use-it" class="glyphicon glyphicon-link anchor"></a>Use it on your site</h2>

  <p>No registration is required to begin using this endpoint.</p>

  <p>On your home page, add a link tag pointing to the token endpoint.</p>

  <p><pre><code><?= htmlspecialchars('<link rel="token_endpoint" href="https://tokens.indieauth.com/token">'); ?></code></pre></p>

  <p>Micropub clients will be able to obtain an access token from this endpoint after you have granted authorization. The Micropub client will then use this access token when making requests to your Micropub endpoint.</p>



  <h2 id="verify"><a href="#verify" class="glyphicon glyphicon-link anchor"></a>Verify Access Tokens</h2>

  <p>When your Micropub endpoint gets a request containing an access token, you can verify the access token and return info about the token by asking the token endpoint.</p>

  <p>Your Micropub endpoint will get a request like the following:</p>

  <p><pre>POST https://example.com/micropub
Authorization: Bearer xxxxxxxx

h=entry&amp;
content=Hello World!</pre></p>

  <p>Your Micropub endpoint can query the token endpoint to verify the access token given. To verify the access token, make a GET request to the token endpoint with the access token in the header:</p>

  <p><pre>GET https://tokens.indieauth.com/token
Accept: application/json
Authorization: Bearer xxxxxxxx</pre></p>

  <p>The token endpoint will verify the token and the response will include information about the user and scope of the token.</p>
  <p>The scope value will be a space-separated list of strings representing all the scopes that were granted. It may also be blank or contain just one value.</p>

  <p><pre>HTTP/1.1 200 OK
Content-Type: application/json

{
  "me": "https://aaronparecki.com/",
  "client_id": "https://ownyourgram.com",
  "scope": "post",
  "issued_at": 1399155608,
  "nonce": 501884823
}</pre></p>

  <p>Your Micropub endpoint can inspect the values and use them to determine whether to proceed with processing the request. For example, for a Micropub endpoint for posting notes to your own site, you would likely only accept requests where the "me" value is your own site.</p>



  <h2 id="indieauth"><a href="#indieauth" class="glyphicon glyphicon-link anchor"></a>Using with IndieAuth.com</h2>

  <p>It is likely that you will use this token endpoint in conjunction with the IndieAuth.com authorization endpoint. By doing so, you can focus on building your Micropub endpoint, and you can swap out the token endpoint or authorization server later.</p>

  <p>To delegate the authorization and token endpoints to indieauth.com, use the following link tags on your site:</p>

  <p><pre><code><?= htmlspecialchars('<link rel="authorization_endpoint" href="https://indieauth.com/auth">'); ?>

<?= htmlspecialchars('<link rel="token_endpoint" href="https://tokens.indieauth.com/token">'); ?></code></pre></p>



  <h2 id="testing"><a href="#testing" class="glyphicon glyphicon-link anchor"></a>Testing</h2>

  <p>To test your Micropub endpoint, you will need to find a Micropub client that you can try to sign in to.</p>

  <p><a href="http://ownyourgram.com/">OwnYourGram.com</a> is a great resource for testing, as it includes documentation and debugging information throughout each step of the login process.</p>



  <h2 id="more"><a href="#more" class="glyphicon glyphicon-link anchor"></a>More Info</h2>

  <ul>
    <li><a href="http://indiewebcamp.com/micropub">Micropub on indiewebcamp.com</a></li>
    <li><a href="https://indieauth.com/">IndieAuth.com</a></li>
    <li><a href="http://ownyourgram.com/creating-a-token-endpoint">Creating your own token endpoint</a></li>
  </ul>


</div>
