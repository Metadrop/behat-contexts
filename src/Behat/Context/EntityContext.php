<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityContext.
 */
class EntityContext extends RawDrupalContext {

  /**
   * Go to last entity created.
   *
   * @Given I go to the last entity :entity created
   * @Given I go to the last entity :entity with :bundle bundle created
   * @Given I go to :subpath of the last entity :entity created
   * @Given I go to :subpath of the last entity :entity with :bundle bundle created
   *
   * @USECORE
   */
  public function goToTheLastEntityCreated($entity_type, $bundle = NULL, $subpath = NULL) {
    $last_entity = $this->getCore()->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = $this->getCore()->entityLoadSingle($entity_type, $last_entity);
    if (!empty($entity)) {
      $uri = $this->getCore()->entityUri($entity_type, $entity);
      $path = empty($subpath) ? $uri : $uri . '/' . $subpath;
      $this->getSession()->visit($this->locatePath($path));
    }
  }

  /**
   * Check entity fields.
   *
   * @Then entity of type :entity_type with field :field_name and value :value should not have the following values:
   */
  public function checkDonotHaveValues($entity_type, $field_name, $value, TableNode $values) {
    $this->checkEntityTestValues($entity_type, $field_name, $value, $values, FALSE);
  }

  /**
   * Check entity fields.
   *
   * @Then entity of type :entity_type with field :field_name and value :value should have the following values:
   *
   * Example:
   * And entity of type 'user' with field 'mail' and value 'behat@metadrop.net' should have the following values:
   *  | mail                | uid                                                  |
   *  | behat@metadrop.net  | entity-replacement:user:mail:behat@metadrop.net:uid |
   */
  public function checkEntityTestValues($entity_type, $field_name, $value, TableNode $values, $throw_error_on_empty = TRUE) {
    $hash = $values->getHash();
    $fields = $hash[0];

    $entity = $this->getCore()->getEntityByField($entity_type, $field_name, $value);
    // Check entity.
    if (isset($entity)) {
      throw new \Exception('Entity of type ' . $entity_type . ' with ' . $field_name . ':  ' . $value . ' not found.');
    }

    $this->getCore()->replacementEntityTokens($fields);
    $errors = $this->getCore()->checkEntityValues();
    $errors = $this->checkEntityFields($entity, $fields);
    if ($throw_error_on_empty && !empty($errors)) {
      throw new \Exception('Failed checking values: ' . implode(', ', $errors));
    }
    elseif (!$throw_error_on_empty && empty($errors)) {
      throw new \Exception('Entity values are correct, but it should not!');
    }
  }

}
