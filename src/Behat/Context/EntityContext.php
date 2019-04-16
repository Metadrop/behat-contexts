<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;

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
   * @Then the :entity_type with field :field_name and value :value should not have the following values:
   */
  public function checkDonotHaveValues($entity_type, $field_name, $value, TableNode $values) {
    $this->checkEntityTestValues($entity_type, $field_name, $value, $values, FALSE);
  }

  /**
   * Check object fields.
   *
   * @Then the :object_type with field :field_name and value :value should have the following values:
   *
   * Example:
   * And the 'user' with field 'mail' and value 'behat@metadrop.net' should have the following values:
   *  | mail                | uid                                                 |
   *  | behat@metadrop.net  | entity-replacement:user:mail:behat@metadrop.net:uid |
   */
  public function checkEntityTestValues($entity_type, $field_name, $value, TableNode $values, $throw_error_on_empty = TRUE) {
    $hash = $values->getHash();
    $fields = $hash[0];

    $entity = $this->getCore()->getEntityByField($entity_type, $field_name, $value);

    // Check entity.
    if (!isset($entity)) {
      throw new \Exception('The ' . $entity_type . ' with ' . $field_name . ':  ' . $value . ' not found.');
    }
    // Make field tokens replacements.
    $fields = $this->replaceTokens($fields);

    // Check entity values and obtain the errors.
    $errors = $this->checkEntityValues($entity, $fields);

    if ($throw_error_on_empty && !empty($errors)) {
      throw new \Exception('Failed checking values: ' . implode(', ', $errors));
    }
    elseif (!$throw_error_on_empty && empty($errors)) {
      throw new \Exception('Entity values are correct, but it should not!');
    }
  }

  protected function checkEntityValues($entity, $fields) {
    $errors = [];
    foreach ($fields as $field => $value) {
      $entity_value = $this->getCore()->getEntityFieldValue($field, $entity);
      if (is_string($value) && is_string($entity_value)) {
        $entity_value = mb_strtolower($entity_value);
        $entity_value = strip_tags($entity_value);
        $entity_value = preg_replace("/\r|\n/", "", $entity_value);
        if (mb_strtolower($value) != $entity_value) {
          $errors[] = ' - Field ' . $field . ': Expected "' . $value . '"; Got "' . $entity_value . '"';
        }
      }
      if (is_array($entity_value) && !in_array($value, $entity_value)) {
        $errors[] = ' - Field ' . $field . ': Expected "' . $value . '"; Got "' . print_r($entity_value, TRUE) . '"';
      }
      if (empty($entity_value) && !empty($value)) {
        $errors[] = ' - Field ' . $field . ': Expected "' . $value . '"; Got empty.';
      }
    }
    return $errors;
  }

  /**
   *  Replacement tokens.
   *
   * @param array|mixed $fields
   *   Fields to replace.
   */
  protected function replaceTokens($values) {
     // Get entity type list.
    $entity_types = $this->getCore()->getEntityTypes();
    foreach ($values as $key => $value) {
      if (strpos($value, 'entity-replacement') === 0) {
        $token_pieces = explode(':', $value);
        array_shift($token_pieces);
        $entity_type = $token_pieces[0];
        $field_key = $token_pieces[1];
        $field_value = $token_pieces[2];
        $destiny_replacement = $token_pieces[3];

        $keys_exists = isset($entity_type) && isset($field_key) && isset($field_value) && isset($destiny_replacement);

        if (!$keys_exists || !in_array($entity_type, $entity_types)) {
          throw new \Exception('Token or entity values are not valid!');
        }
        $entity = $this->getCore()->getEntityByField($entity_type, $field_key, $field_value);

        if (!empty($entity)) {
          $values[$key] = $this->getCore()->getEntityFieldValue($destiny_replacement, $entity, $values[$key]);
        }

      }
    }
    return $values;
  }
}
