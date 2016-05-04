<?php

/**
 * @file
 *
 * DebuggingContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class DebuggingContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @Given /^I wait for "([^"]*)" seconds$/
   *
   * Wait seconds before the next step.
   */
  public function iWaitForSeconds($seconds) {
    sleep($seconds);
  }
}
