<?php

namespace Metadrop\Behat\Context;

/**
 * Default cookie manager implementation.
 *
 * Manages cookie consent interactions.
 */
class DefaultCookieManager implements CookieManagerInterface {

  /**
   * Selector that accepts default cookie categories.
   *
   * @var string
   */
  protected string $cookieAcceptSelector;

  /**
   * Selector that rejects all cookie categories.
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
   *   Selector that accepts default cookie categories.
   * @param string $cookie_reject_selector
   *   Selector that rejects all cookie categories.
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
  public function acceptCookies($session) {
    throw new \Exception('Please, add or use a valid Cookie Manager type (cookie_manager_type) if you need accept the cookies');
  }

  /**
   * {@inheritdoc}
   */
  public function rejectCookies($session) {
    throw new \Exception('Please, add or use a valid Cookie Manager type (cookie_manager_type) if you need reject the cookies.');
  }

}
