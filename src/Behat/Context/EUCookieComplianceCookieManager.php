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
    $session->setCookie("cookie-agreed", "2");
    $session->setCookie("cookie-agreed-categories", '["essential","analytics"]');
    $session->setCookie("cookie-agreed-version", "1.0.0");
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session): void {
    $session->setCookie("cookie-agreed", "0");
  }

  /**
   * {@inheritdoc}
   */
  public function cookiesCategoriesAcceptedStatus(Session $session): array {
    throw new \InvalidArgumentException(
      'To be implemented for EU Cookie Compliance.'
    );
  }
}