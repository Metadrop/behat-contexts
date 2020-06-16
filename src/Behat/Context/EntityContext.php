<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityContext.
 */
class EntityContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Time before scenario.
   *
   * @var string
   */
  protected $timeBeforeScenario = NULL;

  /**
   * Custom Params.
   *
   * @var array|mixed
   */
  protected $customParameters = [];

  protected $entities = [];

  /**
   * Constructor.
   *
   * @param array|mixed $parameters
   *   Parameters.
   */
  public function __construct($parameters = NULL) {

    // Collect received parameters.
    $this->customParameters = [];
    if (!empty($parameters)) {
      // Filter any invalid parameters.
      $this->customParameters = $parameters;
    }

  }

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
    $path = $this->getCore()->buildEntityUri($entity_type, $bundle, $subpath);
    if (!empty($path)) {
      $this->getSession()->visit($this->locatePath($path));
    }
  }

  /**
   * Delete the last entity created.
   *
   * @Given last entity :entity created is deleted
   * @Given last entity :entity with :bundle bundle created is deleted
   *
   * @USECORE
   */
  public function deleteLastEntityCreated($entity_type, $bundle = NULL) {
    $last_entity_id = $this->getCore()->getLastEntityId($entity_type, $bundle);

    if (!empty($last_entity_id)) {
      if ($this->getCore()->entityDelete($entity_type, $last_entity_id) === FALSE) {
        throw new \Exception('The ' . $entity_type . ' with ' . $bundle . ' could not be deleted.');
      }
    }
    else {
      throw new \Exception('The ' . $entity_type . ' with ' . $bundle . ' not found.');
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

  /**
   * Check entity fields loaded by label.
   *
   * @Then the :label :entity_type should have the following values:
   *
   * Example:
   * And the 'Lorem ipsum sit amet' node should have the following values:
   *  | field_bar   | field_foo  |
   *  | Lorem       | ipsum      |
   */
  public function checkEntityByLabelTestValues($entity_type, $label, TableNode $values) {
    $hash = $values->getHash();
    $fields = $hash[0];

    $entity = $this->getCore()->get($entity_type, $label);

    // Check entity.
    if (!$entity instanceof EntityInterface) {
      throw new \Exception('The ' . $entity_type . ' with label ' . $label . ' was not found.');
    }
    // Make field tokens replacements.
    $fields = $this->replaceTokens($fields);

    // Check entity values and obtain the errors.
    $errors = $this->checkEntityValues($entity, $fields);

    if (!empty($errors)) {
      throw new \Exception('Failed checking values: ' . implode(', ', $errors));
    }
  }

  /**
   * Check entity values.
   *
   * @param mixed $entity
   *   Entity.
   * @param array|mixed $fields
   *   Fields to check.
   *
   * @return array|mixed
   *   Errors.
   */
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
   * Replacement tokens.
   *
   * @param array|mixed $values
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

  /**
   * Record time before scenario purge entities.
   *
   * @BeforeScenario @purgeEntities
   */
  public function recordTimeBeforeScenario() {
    $this->timeBeforeScenario = REQUEST_TIME;
  }

  /**
   * Purge entities.
   *
   * Define entities to delete in behat.yml.
   * Example:
   * - Metadrop\Behat\Context\EntityContext:
   *        parameters:
   *          'purge_entities':
   *            - user
   *            - custom_entity.
   *
   * @AfterScenario @purgeEntities
   */
  public function purgeEntities() {
    $condition_key = 'created';
    // Get the request time after scenario and delete entities if the
    // entities were created after scenario execution.
    $condition_value = $this->timeBeforeScenario;
    $purge_entities = !isset($this->customParameters['purge_entities']) ? [] : $this->customParameters['purge_entities'];

    foreach ($purge_entities as $entity_type) {
      $this->getCore()->deleteEntities($entity_type, $condition_key, $condition_value, '>=');
    }

  }

  /**
   * Create entities.
   *
   * @Given :entity_type entity:
   */
  public function entity($entityType, TableNode $entitiesTable) {
    foreach ($entitiesTable->getHash() as $entityHash) {
      $entity = (object) $entityHash;
      $this->entityCreate($entityType, $entity);
    }
  }

  /**
   * Create an entity.
   *
   * During the entity creation, is possible to hook into before
   * entity creation and after, using @beforeEntityCreate
   * and @afterEntityCreate tags.
   *
   * @param string $entity_type
   *   Entity type.
   * @param object $entity
   *   Entity.
   */
  public function entityCreate($entity_type, $entity) {
    $this->dispatchHooks('BeforeEntityCreateScope', $entity, $entity_type);
    $this->parseEntityFields($entity_type, $entity);
    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->create((array) $entity);

    // Check if the field is an entity reference an allow values to be the
    // labels of the referenced entities.
    foreach ($entity as $field_name => $field) {
      if ($field->getFieldDefinition()->getType() === 'entity_reference') {
        $value = $field->getString();
        if (is_numeric($value) === FALSE) {
          $referenced_entity_type = $field->getFieldDefinition()->getSetting('target_type');
          $referenced_entity = $this->getCore()->loadEntityByLabel($referenced_entity_type, $value);
          if ($referenced_entity instanceof EntityInterface) {
            $entity->get($field_name)->get(0)->setValue($referenced_entity->id());
          }
        }
      }
    }

    $saved = $entity->save();
    $this->dispatchHooks('AfterEntityCreateScope', (object) (array) $entity, $entity_type);
    $this->entities[] = $saved;
    return $saved;
  }

}
