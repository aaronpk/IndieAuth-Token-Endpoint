<!doctype html>
<html lang="en">
  <head>
    <title><?= $this->title ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="pingback" href="http://webmention.io/aaronpk/xmlrpc" />
    <link rel="webmention" href="http://webmention.io/aaronpk/webmention" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/style.css">
  </head>

<body role="document">

<div class="page">
  <div class="container">
    <? include($this->page . '.php'); ?>
  </div>

  <div class="footer">
    <p class="credits">&copy; <?=date('Y')?> by <a href="http://aaronparecki.com">Aaron Parecki</a>.
      This code is <a href="https://github.com/aaronpk/IndieAuth-Token-Endpoint">open source</a>. 
      Feel free to send a pull request, or <a href="https://github.com/aaronpk/IndieAuth-Token-Endpoint/issues">file an issue</a>.</p>
  </div>
</div>

</body>
</html>