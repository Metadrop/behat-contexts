<?php

namespace Metadrop\Behat;

/**
 * Trait to get a mink session previously initialized.
 *
 * Use this trait in the case you need session started before visiting the page.
 */
trait MinkContextSessionTrait {

  /**
   * Returns session.
   *
   * @param string|null $name name of the session OR active session will be used
   *
   * @return Session
   */
  public function getSession($name = null)
  {
    $session = parent::getSession($name);
    if (!$session->isStarted()) {
      $session->start();
    }
    return $session;
  }

}
