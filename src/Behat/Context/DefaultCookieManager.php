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
  protected string $cookieAgreeSelector;

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
    string $cookie_agree_selector = '',
    string $cookie_reject_selector = '',
    string $cookie_banner_selector = '',
  ) {
    $this->cookieAgreeSelector = !empty($cookie_agree_selector) ? $cookie_agree_selector : throw new \InvalidArgumentException('Cookie agree selector cannot be empty.');
    $this->cookieRejectSelector = !empty($cookie_reject_selector) ? $cookie_reject_selector : throw new \InvalidArgumentException('Cookie reject selector cannot be empty.');
    $this->cookieBannerSelector = !empty($cookie_banner_selector) ? $cookie_banner_selector : throw new \InvalidArgumentException('Cookie banner selector cannot be empty.');
  }

  /**
   * {@inheritdoc}
   */
  public function getAcceptButtonSelector(): string {
    return $this->cookieAgreeSelector;
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
