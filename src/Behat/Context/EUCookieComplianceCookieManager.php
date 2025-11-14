<?php

namespace Metadrop\Behat\Context;

use Behat\Mink\Session;

/**
 * EU Cookie Compliance cookie manager implementation.
 *
 * Manages EU Cookie Compliance cookie consent interactions.
 */
class EUCookieComplianceCookieManager implements CookieManagerInterface {

  /**
   * Selector to locate the button to accept the default cookie categories.
   *
   * @var string
   */
  protected string $cookieAcceptSelector;

  /**
   * Cookie banner selector.
   *
   * @var string
   */
  protected string $cookieBannerSelector;

  /**
   * EU Cookie Compliance cookie manager constructor.
   *
   * @param string $cookie_agree_selector
   *   Selector to locate the button to accept the default cookie categories.
   * @param string $cookie_banner_selector
   *   Cookie banner selector.
   */
  public function __construct(
    string $cookie_agree_selector,
    string $cookie_banner_selector,
  ) {
    $this->cookieAcceptSelector = empty($cookie_agree_selector) ? '.eu-cookie-compliance-banner button.agree-button' : $cookie_agree_selector;
    $this->cookieBannerSelector = empty($cookie_banner_selector) ? '.eu-cookie-compliance-banner' : $cookie_banner_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function getAcceptButtonSelector(): string {
    return $this->cookieAcceptSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function getRejectButtonSelector(): string {
    throw new \InvalidArgumentException(
      'EU Cookie Compliance banner does not provide a reject button.'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieBannerSelector(): string {
    return $this->cookieBannerSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptCookies($session): void {
    $this->executeEUCookieComplianceMethod($session, 'acceptAllAction');
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session): void {
    $this->executeEUCookieComplianceMethod($session, 'rejectAllAction');
  }

  /**
   * Execute a EUCookieCompliance API method.
   *
   * @param Behat\Mink\Session $session
   *   The current session.
   * @param string $method
   *   The EUCookieCompliance method name to execute.
   */
  protected function executeEUCookieComplianceMethod(Session $session, string $method): void {
    // Wait for the Drupal global object and eu_cookie_compliance function to be defined.
    $session->wait(10000, "typeof window.Drupal === 'object' && window.Drupal !== null && typeof window.Drupal.eu_cookie_compliance === 'function'");

    // Wait for the API method to be ready and execute.
    $session->wait(5000, "typeof window.Drupal.eu_cookie_compliance.{$method} === 'function'");
    $session->executeScript("window.Drupal.eu_cookie_compliance.{$method}();");
  }

}
