<?php

/**
 * @file
 *
 * DrupalUtilsContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class DrupalUtilsContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @Given I run elysia cron
   *
   * Run elysia-cron.
   */
  public function iRunElysiaCron() {
    elysia_cron_run(TRUE);
  }

  /**
   * Check the user with a specific mail have a specific role.
   *
   * @param string $mail
   *  Value mail
   * @param string $role
   *   Rol name
   *
   * @Then /^user with mail "([^"]*)" should have the role "([^"]*)"$/
   */
  public function userWithMailShouldHaveTheRole($mail, $role) {
    $uid = db_query("SELECT uid FROM {users} WHERE mail= :mail", array(':mail' => $mail))->fetchField();
    $account = user_load($uid);
    if (!in_array($role, $account->roles)) {
      throw new Exception("Given user has not the role $role");
    }
  }

  /**
   * Checks if a form element is required.
   *
   * It relys on the requeried class added to he element by Drupal. This
   * approach doesn't work with file type input elements.
   *
   * @Then form :type element :label should be required
   */
  public function formElementShouldBeRequired($type, $label) {
    $page = $this->getSession()->getPage();
    $xpath = "//label[contains(text(), '{$label}')]/..//{$type}[contains(@class, 'required')]";
    $element = $page->find('xpath', $xpath);
    if (NULL === $element) {
      throw new \InvalidArgumentException("Could not find the form element \"$label\" of type \"$type\"");
    }
  }
}
