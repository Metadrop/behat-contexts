<?php

namespace Metadrop\Behat\Context;

use Behat\Mink\Session;

/**
 * OneTrust cookie manager implementation.
 *
 * Manages OneTrust cookie consent interactions.
 */
class OneTrustCookieManager implements CookieManagerInterface {

  /**
   * Selector to locate the button to accept the default cookie categories.
   *
   * @var string
   */
  protected string $cookieAcceptSelector;

  /**
   * Selector to locate the button to reject all cookie categories.
   *
   * @var string
   */
  protected string $cookieRejectSelector;

  /**
   * Cookie banner selector.
   *
   * @var string
   */
  protected string $cookieBannerSelector;

  /**
   * OneTrust cookie manager constructor.
   *
   * @param string $cookie_agree_selector
   *   Selector to locate the button to accept the default cookie categories.
   * @param string $cookie_reject_selector
   *   Selector to locate the button to reject all cookie categories.
   * @param string $cookie_banner_selector
   *   Cookie banner selector.
   */
  public function __construct(
    string $cookie_agree_selector,
    string $cookie_reject_selector,
    string $cookie_banner_selector,
  ) {
    $this->cookieAcceptSelector = empty($cookie_agree_selector) ? '#onetrust-accept-btn-handler' : $cookie_agree_selector;
    $this->cookieRejectSelector = empty($cookie_reject_selector) ? '#onetrust-reject-all-handler' : $cookie_reject_selector;
    $this->cookieBannerSelector = empty($cookie_banner_selector) ? '#onetrust-banner-sdk' : $cookie_banner_selector;
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
    return $this->cookieRejectSelector;
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
    $this->executeOneTrustMethod($session, 'AllowAll');
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session): void {
    $this->executeOneTrustMethod($session, 'RejectAll');
  }

  /**
   * Execute a OneTrust API method.
   *
   * @param Behat\Mink\Session $session
   *   The current session.
   * @param string $method
   *   The OneTrust method name to execute.
   */
  protected function executeOneTrustMethod(Session $session, string $method): void {
    // Wait for the OneTrust API method to be ready.
    if (!$session->wait(10000, "typeof window.OneTrust === 'object' && window.OneTrust !== null
    && typeof window.OneTrust.{$method} === 'function'")) {
      throw new \InvalidArgumentException(
        "OneTrust '.{$method}()' function does not exist or has not loaded correctly."
      );
    }
    // Execute OneTrust API method.
    $session->executeScript("window.OneTrust.{$method}();");
  }

}
