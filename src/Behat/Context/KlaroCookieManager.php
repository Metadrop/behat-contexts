<?php

namespace Metadrop\Behat\Context;

use Behat\Mink\Session;

/**
 * Klaro cookie manager implementation.
 *
 * Manages Klaro cookie consent interactions.
 */
class KlaroCookieManager implements CookieManagerInterface {

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
   * Klaro cookie manager constructor.
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
    $this->cookieAcceptSelector = empty($cookie_agree_selector) ? '#klaro .cm-btn.cm-btn-success' : $cookie_agree_selector;
    $this->cookieRejectSelector = empty($cookie_reject_selector) ? '#klaro .cm-btn.cn-decline' : $cookie_reject_selector;
    $this->cookieBannerSelector = empty($cookie_banner_selector) ? '#klaro-cookie-notice' : $cookie_banner_selector;
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
    $this->setAcceptanceStatusForAllCookies($session, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session): void {
    $this->setAcceptanceStatusForAllCookies($session, FALSE);
  }

  /**
   * Waits a few second until the Klaro JS object appears in the window object.
   *
   * @param Session $session
   *   The current Mink session.
   *
   * @return void
   */
  protected function waitForKlaroObjectAvailability(Session $session): void {
    // Wait for the Klaro global object to be defined.
    if (!$session->wait(10000, "typeof window.klaro === 'object' && window.klaro !== null
      && typeof window.klaro.getManager === 'function'
      && typeof window.klaro.getManager().changeAll === 'function'
      && typeof window.klaro.getManager().saveAndApplyConsents === 'function'")) {
      throw new \InvalidArgumentException(
        "Klaro API does not exist or has not loaded correctly."
      );
    }
  }

  /**
   * Execute a Klaro API method.
   *
   * @param Session $session
   *   The current Mink session.
   * @param boolean $value
   *   The Klaro method name to execute.
   */
  protected function setAcceptanceStatusForAllCookies(Session $session, bool $value): void {

    $this->waitForKlaroObjectAvailability($session);

    // Declare JS script and Klaro API methods.
    $jsValue = $value ? 'true' : 'false';
    $script = "
      const manager = window.klaro.getManager();
      manager.changeAll({$jsValue});
      manager.saveAndApplyConsents();
    ";
    $session->executeScript($script);
  }

  /**
   * {@inheritdoc}
   */
  public function cookiesCategoriesAcceptedStatus(Session $session): array {

    $this->waitForKlaroObjectAvailability($session);

    // Declare JS script and execute.
    $script = "
      return window.klaro.getManager().consents;
    ";
    $result = $session->evaluateScript($script);

    return is_array($result) ? $result : [];
  }

}
