<?php

/**
 * SimpleView
 *
 * The SimpleView is a Custom View class that renders templates
 */
class SimpleView extends \Slim\View
{
  public static $template_path = 'views';

  public function render($template, $data=null)
  {
    ob_start();
    require(self::$template_path . '/' . $template);
    return ob_get_clean();
  }

  public function __get($key) {
    return $this->get($key);
  }
}
