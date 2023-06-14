<?php

namespace Metadrop\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Element\NodeElement;

/**
 * Allows checking that the sites are cookie gdpr compliant.
 *
 * It checks that before accepting cookies there are not cookies
 * saved in the browser, and when the cookies are accepted, the
 * expected cookies appears.
 */
class CookieComplianceContext extends RawMinkContext {

  /**
   * Selector that accepts default cookie categories.
   *
   * @var string
   */
  protected string $cookieAgreeSelector;

  /**
   * Cookie banner selector.
   *
   * @var string
   */
  protected string $cookieBannerSelector;

  /**
   * List of cookies classified with category.
   *
   * @var array
   */
  protected array $cookies;

  /**
   * Cookie compliance constructor.
   *
   * @param string $cookie_agree_selector
   *   Selector that accepts default cookie categories.
   * @param string $cookie_banner_selector
   *   Cookie banner selector.
   * @param array $cookies
   *   List of cookies classified with category.
   */
  public function __construct(string $cookie_agree_selector, string $cookie_banner_selector, array $cookies) {
    $this->cookieAgreeSelector = $cookie_agree_selector;
    $this->cookieBannerSelector = $cookie_banner_selector;
    $this->cookies = $cookies;
  }

  /**
   * Accept cookie compliance.
   *
   * It accepts it by accepting all the categories
   * that are accepted by default.
   *
   * @Then I accept cookies
   */
  public function iAcceptCookies() {
    $agree_button = $this->getSession()->getPage()->find('css', $this->cookieAgreeSelector);
    if ($agree_button instanceof NodeElement) {
      $agree_button->press();
      if (!$this->getSession()->wait(10000, sprintf('document.querySelector("%s") == null', $this->cookieBannerSelector))) {
        throw new \Exception(sprintf('The cookie banner with selector "%s" is stil present after accepting cookies.', $this->cookieBannerSelector));
      }
    }
    else {
      throw new \Exception('The agree button do not appears.');
    }
  }

  /**
   * Check the main cookies of a specific type are not present.
   *
   * @Then there should not be cookies of :type type
   */
  public function cookiesShouldBeEmpty(string $type) {
    $cookies_list = $this->cookies[$type] ?? [];
    foreach ($cookies_list as $cookie_name) {
      $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
      if (!empty($cookie_value)) {
        throw new \Exception(sprintf("Cookie with name %s is saved, but it shouldn\'t.", $cookie_name));
      }
    }
  }

  /**
   * Check the main cookies of a specific type are present.
   *
   * @Then the cookies of :type type have been saved
   */
  public function cookiesHaveBeenSaved(string $type) {
    $cookies_list = $this->cookies[$type] ?? [];
    foreach ($cookies_list as $cookie_name) {
      $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
      if (empty($cookie_value)) {
        throw new \Exception(sprintf('Cookie with name %s is not saved, but it should.', $cookie_name));
      }
    }
  }

  /**
   * Wait until the cookie banner appears.
   *
   * @When I wait cookie banner appears
   */
  public function iWaitCookieBannerAppears() {
    if (!$this->getSession()->wait(10000, sprintf('document.querySelector("%s") != null', $this->cookieBannerSelector))) {
      throw new \Exception(sprintf('The cookie banner with selector "%s" does not appear.', $this->cookieBannerSelector));
    }
  }

}
