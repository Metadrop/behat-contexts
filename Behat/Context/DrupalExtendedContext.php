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

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    $query->entityCondition('bundle', $bundle);
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
