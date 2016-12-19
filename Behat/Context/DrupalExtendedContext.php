<?php

/**
 * @file
 *
 * DrupalExtendedContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class DrupalExtendedContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Gets info about required state of a form element.
   *
   * It relies on the requeried class added to he element by Drupal. This
   * approach doesn't work with file type input elements.
   *
   * @param string $label
   *   Form element label.
   * @param string $type
   *   Form element type.
   * @throws \InvalidArgumentException
   */
  protected function isFormElementRequired($type, $label) {
    if ($label === 'file') {
      throw new \InvalidArgumentException("Form element \"file\" type not supported");
    }

    $page = $this->getSession()->getPage();

    // Try to find element.
    $xpath_element = "//label[contains(text(), '{$label}')]/..//{$type}";
    $element = $page->find('xpath', $xpath_element);
    if (NULL === $element) {
      throw new \InvalidArgumentException("Could not find the form element \"$label\" of type \"$type\"");
    }

    // Check required class.
    $xpath_required = "//label[contains(text(), '{$label}')]/..//{$type}[contains(@class, 'required')]";
    $element_required = $page->find('xpath', $xpath_required);

    return NULL !== $element_required;
  }

  /**
   * Flush page cache.
   *
   * @param string $path
   *  Page name without first "/"
   *  Use "*" as wildcard. Example: articles/*
   *
   * @Given :path page cache is flushed
   */
  public function pageCacheIsFlushed($path = NULL) {
    global $base_url;

    if (!empty($path) && $path !== '*') {
      $path = $base_url . '/' . $path;
    }

    cache_clear_all($path, 'cache_page', TRUE);
  }

  /**
   * Flush views data cache.
   *
   * @param string $views_name
   *  Views name
   *
   * @Given :view view data cache is flushed
   */
  public function viewDataCacheIsFlushed($views_name) {
    cache_clear_all($views_name . ':', 'cache_views_data', TRUE);
  }

  /**
   * @Given I run elysia cron
   *
   * Run elysia-cron.
   */
  public function iRunElysiaCron() {
    elysia_cron_run(TRUE);
  }

  /**
   * Gets user property by name.
   *
   * This function tries to figure out which kind to identificator is refering to
   * in an "smart" way.
   *
   * @param string $name
   *   The identifier
   *   Examples: "admin", "12", "example@example.com"
   *
   * @return string
   *   The property
   */
  public function getUserPropertyByName($name) {
    if (valid_email_address($name)) {
      $property = 'mail';
    }
    elseif (is_numeric($name)) {
      $property = 'uid';
    }
    else {
      $property = 'name';
    }
    return $property;
  }

  /**
   * Gets the user that matches the condition.
   *
   * @param $condition
   *   Query condition: mail, name, uid.
   * @param $value
   *   Value to compare (equal)
   */
  public function getUserByCondition($condition, $value, $reset = TRUE) {
    $query = db_select('users');
    $query->fields('users', array('uid'));
    $query->condition($condition, $value);

    $result = $query->execute();
    $uid    = $result->fetchField();

    $account = user_load($uid, $reset);
    return $account;
  }

  /**
   * Check the user with a specific mail have a specific role.
   *
   * @param string $role
   *   Role name(s) separated by comma.
   * @param string $user
   *   User identifier: username | mail | uid or NULL to current user.
   * @param bool $not
   *   Checks if should have or not.
   *
   * @Then I should have the :role role(s)
   * @Then the user :user should have the :role role(s)
   */
  public function userShouldHaveTheRole($role, $user = NULL, $not = FALSE) {

    if (empty($user)) {
      $account = $this->user;
    }
    else {
      $condition = $this->getUserPropertyByName($user);
      $account = $this->getUserByCondition($condition, $user);
    }

    if ($account) {
      $roles = explode(',', $role);
      $roles = array_map('trim', $roles);
      foreach ($roles as $role) {
        if (!$not && !in_array($role, $account->roles)) {
          throw new \Exception("Given user does not have the role $role");
        }
        else if ($not && in_array($role, $account->roles)) {
          throw new \Exception("Given user have the role $role");
        }
      }
    }
    else {
      throw new \Exception("Given user does not exists!");
    }
  }

  /**
   * Check the user with a specific mail have a specific role.
   *
   * @param string $role
   *   Role name(s) separated by comma.
   * @param string $user
   *   User identifier: username | mail | uid or NULL to current user.
   *
   * @Then I should not have the :role roles(s)
   * @Then the user :user should not have the :role role(s)
   */
  public function userShouldNotHaveTheRole($role, $user = NULL) {
    return $this->userShouldHaveTheRole($role, $user, TRUE);
  }

  /**
   * Checks if a form element is required.
   *
   *
   * @Then form :type element :label should be required
   */
  public function formElementShouldBeRequired($type, $label) {
    if (!$this->isFormElementRequired($type, $label)) {
      throw new \InvalidArgumentException("Form element \"$label\" of type \"$type\" is not required");
    }
  }

  /**
   * Checks if a form element is not required.
   *
   * @Then form :type element :label should not be required
   */
  public function formElementShouldNotBeRequired($type, $label) {
    if ($this->isFormElementRequired($type, $label)) {
      throw new \InvalidArgumentException("Form element \"$label\" of type \"$type\" is required");
    }
  }
}
