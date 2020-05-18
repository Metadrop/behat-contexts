<?php

namespace Metadrop\Behat;

use Behat\Mink\Session;

/**
 * Trait to get a mink session previously initialized.
 *
 * Use this trait in the case you need session started before visiting the page.
 */
trait MinkContextSessionTrait {

  /**
   * Asserts a sesson has been started.
   *
   * @param Session $session
   *   Mink session.
   */
  public function assertSessionStarted(Session $session)
  {
    if (!$session->isStarted()) {
      $session->start();
    }
  }

}
