<?php

namespace Metadrop\Behat\Context;

use Behat\Mink\Session;

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
  public function acceptCookies(Session $session): void;

  /**
   * Reject cookies programatically.
   *
   * Do the required actions in the browser so the cookie
   * manager considers cookies has been rejected.
   */
  public function rejectCookies(Session $session): void;

  /**
   * Get the acceptance status of cookie categories.
   *
   * Returns an associative array where keys are category names
   * and values are booleans indicating if the category is accepted.
   *
   * @param mixed $session
   *   The Mink session object.
   *
   * @return array
   *   Array with category names as keys and acceptance status (bool) as values.
   *   Example: ['analytics' => true, 'marketing' => false, 'necessary' => true]
   */
  public function cookiesCategoriesAcceptedStatus(Session $session): array;

}
