<?php

namespace Metadrop\Behat\Context;

/**
 * Interface for cookie manager services.
 *
 * Defines methods for managing cookie consent banners and interactions.
 */
interface CookieManagerInterface {

  /**
   * Get the CSS selector for the accept button.
   *
   * @return string
   *   The CSS selector for the accept cookies button.
   */
  public function getAcceptButtonSelector(): string;

  /**
   * Get the CSS selector for the reject button.
   *
   * @return string
   *   The CSS selector for the reject cookies button.
   */
  public function getRejectButtonSelector(): string;

  /**
   * Get the CSS selector for the cookie banner.
   *
   * @return string
   *   The CSS selector for the cookie banner container.
   */
  public function getCookieBannerSelector(): string;

  /**
   * Accept cookies programatically.
   *
   * Do the required actions in the browser so the cookie
   * manager considers cookies has been accepted.
   */
  public function acceptCookies(string $sesion);

  /**
   * Reject cookies programatically.
   *
   * Do the required actions in the browser so the cookie
   * manager considers cookies has been rejected.
   */
  public function rejectCookies(string $sesion);

}
