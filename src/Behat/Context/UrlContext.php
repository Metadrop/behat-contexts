<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;

class UrlContext extends RawMinkContext {

  /**
   * Check param with value in url.
   *
   * @Then current url should have the ":param" param with ":value" value
   */
  public function urlShouldHaveParamWithValue($param, $value, $have = TRUE) {
    $url = $this->getSession()->getCurrentUrl();
    $queries = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $queries);
    if (!(isset($queries[$param]) && $queries[$param] == $value) && $have) {
      throw new \Exception("The param " . $param . " with value " . $value . " is not in the url");
    }
    elseif (isset($queries[$param]) && $queries[$param] == $value && !$have) {
      throw new \Exception("The param " . $param . " with value " . $value . " is in the url");
    }
  }

  /**
   * Check param with value in url not exists.
   *
   * @Then current url should not have the ":param" param with ":value" value
   */
  public function urlShouldNotHaveParamWithValue($param, $value) {
    $this->urlShouldHaveParamWithValue($param, $value, FALSE);
  }

}