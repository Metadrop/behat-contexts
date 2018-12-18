<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;

class WaitingContext extends RawMinkContext {

  /**
   * Wait for AJAX to finish.
   *
   * @param int $seconds
   *   Max time to wait for AJAX.
   *
   * @Given I wait for AJAX to finish at least :seconds seconds
   *
   * @throws \Exception
   *   Ajax call didn't finish on time.
   */
  public function iWaitForAjaxToFinish($seconds) {
    $finished = $this->getSession()->wait($seconds * 1000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$finished) {
      throw new \Exception("Ajax call didn't finished within $seconds seconds.");
    }
  }

  /**
   * Wait for batch process.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 5 seconds (5,000 ms).
   *
   * @param init $seconds
   *
   * @Given I wait for the batch job to finish
   * @Given I wait for the batch job to finish at least :seconds seconds
   */
  public function iWaitForTheBatchJobToFinish($seconds = 5) {
    $this->getSession()->wait($seconds * 1000, 'jQuery("#updateprogress").length === 0');
  }

}