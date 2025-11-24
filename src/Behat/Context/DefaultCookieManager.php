<?php

namespace Metadrop\Behat\Context;

use Behat\Mink\Session;

/**
 * Default cookie manager implementation.
 *
 * Manages cookie consent interactions with an unknown cookie manager.
 * This implies that the selector for the banner and reject and accept
 * buttons must be provided. Also, accepting and rejecting cookies can't
 * be done programmatically, because the cookie manager is unknown and
 * there is no way to automatically discover how to do it.
 */
class DefaultCookieManager implements CookieManagerInterface {

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
   * Default cookie manager constructor.
   *
   * @param string $cookie_agree_selector
   *   Selector to locate the button to accept the default cookie categories.
   * @param string $cookie_reject_selector
   *   Selector to locate the button to reject all cookie categories.
   * @param string $cookie_banner_selector
   *   Cookie banner selector.
   *
   * @throws \InvalidArgumentException
   *   If any of the selectors is empty.
   */
  public function __construct(
    string $cookie_agree_selector,
    string $cookie_reject_selector,
    string $cookie_banner_selector,
  ) {
    $this->cookieAcceptSelector = $cookie_agree_selector;
    $this->cookieRejectSelector = $cookie_reject_selector;
    $this->cookieBannerSelector = $cookie_banner_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function getAcceptButtonSelector(): string {
    if (empty($this->cookieAcceptSelector)) {
      throw new \InvalidArgumentException('The Cookie accept selector (cookie_agree_selector) cannot be empty.');
    }

    return $this->cookieAcceptSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function getRejectButtonSelector(): string {
    if (empty($this->cookieRejectSelector)) {
      throw new \InvalidArgumentException('The Cookie reject selector (cookie_reject_selector) cannot be empty.');
    }

    return $this->cookieRejectSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieBannerSelector(): string {
    if (empty($this->cookieBannerSelector)) {
      throw new \InvalidArgumentException('The Cookie banner selector (cookie_banner_selector) cannot be empty');
    }

    return $this->cookieBannerSelector;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptCookies($session): void {
    throw new \Exception('Please, add or use a valid Cookie Manager type (cookie_manager_type) if you need to accept cookies');
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session): void {
    throw new \Exception('Please, add or use a valid Cookie Manager type (cookie_manager_type) if you need to reject cookies');
  }

  /**
   * {@inheritdoc}
   */
  public function cookiesCategoriesAcceptedStatus(Session $session): array {
    throw new \InvalidArgumentException(
      'Category cookies acceptance status not available for unknown cookie managers'
    );
  }
}
