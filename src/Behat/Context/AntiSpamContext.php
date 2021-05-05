<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;

class AntiSpamContext extends RawMinkContext {

  /**
   * The honeypot time limit
   */
  protected $honeypotTimeLimit;

  /**
   * Disable honeypot time limit value.
   *
   * To be used when the presence of a honeypot time limit is provoking
   * false negatives on tests.
   *
   * @BeforeScenario @honeypot-disable
   */
  public function disableHoneypot() {
    print('Honeypot: disabling time limit');
    $this->honeypotTimeLimit = $this->getCore()->getHoneypotLimit();
    $this->getCore()->setHoneypotLimit(0);
  }

  /**
   * Restore honeypot time limit value.
   *
   * @AfterScenario @honeypot-disable
   */
  public function restoreHoneypot() {
    print('Honeypot: restoring time limit');
    $this->getCore()->setHoneypotLimit($this->honeypotTimeLimit);
  }

}
