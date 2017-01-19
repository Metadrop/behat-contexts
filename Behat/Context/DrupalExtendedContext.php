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
   * @Given I run the elysia cron :job job
   *
   * Run elysia-cron-job.
   */
  public function iRunElysiaCronJob($job) {
    // @NOTE We force it
    elysia_cron_run_job($job, TRUE, TRUE, TRUE);
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
   * Check the user has or not a specific role.
   *
   * @param string $role
   *   Role name(s) separated by comma.
   * @param string $user
   *   User identifier: username | mail | uid or NULL to current user.
   * @param bool $not
   *   True if the user should NOT have the specific roles.
   */
  public function userRoleCheck($role, $user = NULL, $not = FALSE) {
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
      // Case insensitive:
      $roles = array_map('strtolower', $roles);
      $aroles = array_map('strtolower', $account->roles);
      foreach ($roles as $role) {
        if (!$not && !in_array($role, $aroles)) {
          throw new \Exception("Given user does not have the role $role");
        }
        else if ($not && in_array($role, $aroles)) {
          throw new \Exception("Given user have the role $role");
        }
      }
    }
    else {
      throw new \Exception("Given user does not exists!");
    }
  }

  /**
   * Check the user has a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should have the :role role(s)
   * @Then the user :user should have the :role role(s)
   */
  public function userShouldHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user);
  }

  /**
   * Check the user does not have a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should not have the :role roles(s)
   * @Then the user :user should not have the :role role(s)
   */
  public function userShouldNotHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user, TRUE);
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


  /**
   * Get last entity id created
   *
   * @param string $entity_type
   *   Entity type
   * @param string $bundle
   *   Entity bundle
   *
   * @return integer
   *   Entity Id
   */
  public function getLastEntityId($entity_type, $bundle = NULL) {

    $info = entity_get_info($entity_type);
    $id_key = $info['entity keys']['id'];
    $bundle_key = $info['entity keys']['bundle'];

    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    if ($bundle) {
      $query->entityCondition($bundle_key, $bundle);
    }

    $query->propertyOrderBy($id_key, 'DESC');
    $query->range(0, 1);
    $query->addMetaData('account', user_load(1));

    $result = $query->execute();
    $keys = array_keys($result[$entity_type]);
    $id = reset($keys);

    if (empty($id)){
      throw new \Exception("Can't take last one");
    }

    return $id;
  }

  /**
   * Go to last entity created.
   *
   * @Given I go to the last entity :entity created
   * @Given I go to the last entity :entity with :bundle bundle created
   * @Given I go to :subpath of the last entity :entity created
   * @Given I go to :subpath of the last entity :entity with :bundle bundle created
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Entity bundle.
   * @param string $subpath
   *   Entity bundle.
   */
  public function goToTheLastEntityCreated($entity_type, $bundle = NULL, $subpath = NULL) {
    $last_entity = $this->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = entity_load_single($entity_type, $last_entity);
    if (!empty($entity)) {
      $uri = entity_uri($entity_type, $entity);
      $path = empty($subpath) ? $uri['path'] : $uri['path'] . '/' . $subpath;
      $this->getSession()->visit($this->locatePath($path));
    }
  }

}
