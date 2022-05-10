<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Symfony\Component\Serializer\Exception\UnsupportedException;

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

  protected $users = [];

  protected $nodes = [];

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
   */
  public function goToTheLastEntityCreated($entity_type, $bundle = NULL, $subpath = NULL) {
    $last_entity = $this->getCore()->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = $this->getCore()->entityLoadSingle($entity_type, $last_entity);
    $path = $this->getCore()->buildEntityUri($entity_type, $entity, $subpath);
    if (!empty($path)) {
      $this->getSession()->visit($this->locatePath($path));
    }
  }

  /**
   * Go to a specific path of an entity with a specific label.
   *
   * @Given I go to the :entity_type entity with label :label
   * @Given I go to the :entity_type entity with label :label in :language language
   * @Given I go to :subpath of the :entity_type entity with label :label
   */
  public function goToTheEntityWithLabel($entity_type, $label, $subpath = NULL, $language = NULL) {
    $entity = $this->getCore()->loadEntityByLabel($entity_type, $label);
    $path = $this->getCore()->buildEntityUri($entity_type, $entity, $subpath);
    if ($language) {
      $prefix = $this->getCore()->getLanguagePrefix($language);
      $path = $prefix . '/' . $path;
    }
    if (!empty($path)) {
      $this->getSession()->visit($this->locatePath($path));
    }
  }

  /**
   * Go to a specific path of an entity with an specific properties.
   *
   * @Given I go to the :entity_type entity with properties:
   * @Given I go to :subpath of the :entity_type entity with properties:
   * @Given I go to the :entity_type entity in :language language with properties:
   */
  public function goToTheEntityWithProperties($subpath, $entity_type, $language=NULL, TableNode $properties) {
    $properties_filter = [];
    foreach ($properties->getIterator() as $property){
      $properties_filter[key($property)] = $property[key($property)];
    }
    $entity = $this->getCore()->loadEntityByProperties($entity_type, $properties_filter);
    $path = $this->getCore()->buildEntityUri($entity_type, $entity, $subpath);

    if ($language) {
      $prefix = $this->getCore()->getLanguagePrefix($language);
      $path = $prefix . '/' . $path;
    }
    if (!empty($path)) {
      $this->visitPath($path);
    } else{
      throw new \Exception("Error: Entity or path not found");
    }
  }

  /**
   * Delete the last entity created.
   *
   * @Given last entity :entity created is deleted
   * @Given last entity :entity with :bundle bundle created is deleted
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
   * @Then the :entity_type with field :field_name and value :value translation :langcode should have the following values:
   */
  public function checkEntityTranslationValues($entity_type, $field_name, $value, $langcode, TableNode $values, $check_correct = TRUE) {
    $entity = $this->getCore()->getEntityByField($entity_type, $field_name, $value);

    // Check entity.
    if (!isset($entity)) {
      throw new \Exception('The ' . $entity_type . ' with ' . $field_name . ':  ' . $value . ' not found.');
    }
    if (!$entity->hasTranslation($langcode)) {
      throw new \Exception('Entity does not have ' . $langcode . ' translation.');
    }
    $this->checkGivenEntityValues($entity->getTranslation($langcode), $values, $check_correct);
  }

  /**
   * @Then the :entity_type with field :field_name and value :value translation :langcode should not have the following values:
   */
  public function checkEntityTranslationNotValues($entity_type, $field_name, $value, $langcode, TableNode $values) {
    $this->checkEntityTranslationValues($entity_type, $field_name, $value, $langcode, $values, FALSE);
  }

  /**
   * @Then the :entity_type with field :field_name and value :value should not have :langcode translation
   */
  public function checkEntityTranslationNotExists($entity_type, $field_name, $value, $langcode) {
    $this->checkEntityTranslationExists($entity_type, $field_name, $value, $langcode, FALSE);
  }

  /**
   * @Then the :entity_type with field :field_name and value :value should have :langcode translation
   */
  public function checkEntityTranslationExists($entity_type, $field_name, $value, $langcode, $check_exists = TRUE) {
    $entity = $this->getCore()->getEntityByField($entity_type, $field_name, $value);

    // Check entity.
    if (!isset($entity)) {
      throw new \Exception('The ' . $entity_type . ' with ' . $field_name . ':  ' . $value . ' not found.');
    }
    if ($check_exists && !$entity->hasTranslation($langcode)) {
      throw new \Exception('Entity does not have ' . $langcode . ' translation and should.');
    }
    else {
      if (!$check_exists && $entity->hasTranslation($langcode)) {
        throw new \Exception('Entity does not have ' . $langcode . ' translation and should not.');
      }
    }
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
    $entity = $this->getCore()->getEntityByField($entity_type, $field_name, $value);

    // Check entity.
    if (!$entity instanceof EntityInterface) {
      throw new \Exception('The ' . $entity_type . ' with ' . $field_name . ':  ' . $value . ' not found.');
    }
    $this->checkGivenEntityValues($entity, $values, $throw_error_on_empty);
  }

  /**
   * Check given entity values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   * @param \Behat\Gherkin\Node\TableNode $values
   *   Values to evalate.
   * @param bool $check_correct
   *   Check correct or incorrect values.
   */
  protected function checkGivenEntityValues(EntityInterface $entity, TableNode $values, $check_correct = TRUE) {
    $hash = $values->getHash();
    $fields = $hash[0];
    // Make field tokens replacements.
    $fields = $this->replaceTokens($fields);

    // Check entity values and obtain the errors.
    $errors = $this->checkEntityValues($entity, $fields);

    if ($check_correct && !empty($errors)) {
      throw new \Exception('Failed checking values: ' . implode(', ', $errors));
    }
    elseif (!$check_correct && empty($errors)) {
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

    // Reset cache on load entities because this step is aim to be used many
    // times after saving a content. If the saving process is triggered by a
    // form, the reset of static cache would be done in a different process, so
    // the main process would be getting the cached version of the entity.
    $entity = $this->getCore()->loadEntityByLabel($entity_type, $label, TRUE);

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
      elseif (strpos($value, 'relative-date:') === 0) {
        $values[$key] = strtotime(str_replace('relative-date:', '', $value));
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
    if ($this->getDriver() instanceof BlackboxDriver) {
      throw new UnsupportedDriverActionException('No ability to purge entities, put @api in your scenario.', $this->getDriver());
    }

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
    // Get the request time after scenario and delete entities if the
    // entities were created after scenario execution.
    $condition_value = $this->timeBeforeScenario;
    $purge_entities = !isset($this->customParameters['purge_entities']) ? [] : $this->customParameters['purge_entities'];
    $given_entities = $this->getGivenEntitiesMap();

    foreach ($purge_entities as $entity_type) {

      // Not every entity type has a 'changed' property.
      // If it occurs more frequently the behat.yml
      // should change to allow define what property
      // can be used to get the created time.
      switch ($entity_type) {
        case 'taxonomy_term':
          $condition_key = 'changed';
          break;

        default:
          $condition_key = 'created';
      }

      $entities_ids = $this->getCore()->getEntitiesWithCondition($entity_type, $condition_key, $condition_value, '>=');
      if (!empty($given_entities[$entity_type])) {
        $entities_ids = array_diff($entities_ids, $given_entities[$entity_type]);
      };

      foreach (array_reverse($entities_ids) as $id) {
        $this->getCore()->entityDelete($entity_type, $id, TRUE);
      }
    }
  }

  /**
   * Get the entities created on Given steps.
   *
   * @return array
   *   An array of ids grouped by entity type.
   */
  protected function getGivenEntitiesMap(): array {
    $map = [];

    foreach ($this->entities as $item) {
      $map[$item['entity_type']][] = $item['entity_id'];
    };

    $map['user'] = $this->users;
    $map['node'] = $this->nodes;

    return $map;
  }

  /**
   * Create entities.
   *
   * @Given :entity_type entity:
   */
  public function entity($entityType, TableNode $entitiesTable) {
    foreach ($entitiesTable->getHash() as $entityHash) {
      $fields = $this->replaceTokens($entityHash);
      $entity = (object) $fields;
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
    $reference_types = [
      'entity_reference',
      'file',
      'image',
    ];
    foreach ($entity as $field_name => $field) {
      if (in_array($field->getFieldDefinition()->getType(), $reference_types) && !$field->getFieldDefinition()->isComputed()) {
        $values = $field->getValue();
        foreach ($values as $key => $value) {
          if (is_array($value) && !empty($value['target_id'])) {
            $referenced_entity_type = $field->getFieldDefinition()->getSetting('target_type');
            $referenced_entity = $this->getCore()->loadEntityByLabel($referenced_entity_type, $value['target_id']);
            if ($referenced_entity instanceof EntityInterface) {
              if ($key === 0 && $entity->get($field_name)->isEmpty()) {
                $entity->get($field_name)->setValue($referenced_entity->id());
              }
              else {
                $entity->get($field_name)->get($key)->setValue($referenced_entity->id());
              }
            }
          }
        }
      }
    }

    $saved = $entity->save();
    $this->dispatchHooks('AfterEntityCreateScope', (object) (array) $entity, $entity_type);
    $this->entities[] = [
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ];

    return $saved;
  }

  /**
   * @afterNodeCreate
   */
  public function afterNodeCreate(AfterNodeCreateScope $scope) {
    $node = $scope->getEntity();
    $this->nodes[] = $node->nid;
  }

  /**
   * @afterUserCreate
   */
  public function afterUserCreate(AfterUserCreateScope $scope) {
    $user = $scope->getEntity();
    $this->users[] = $user->uid;
  }

  /**
   * @AfterScenario
   */
  public function cleanEntities() {

    // In some cases (as Group and Group Content), some entities need to be
    // deleted by its parent and not manually or independently.
    // The 'entities_clean_bypass' allows to define some entities that will be
    // skipped. It might lead to database pollution, so use it carefully.
    $bypass_entities = isset($this->customParameters['entities_clean_bypass']) ? $this->customParameters['entities_clean_bypass'] : [];

    foreach (array_reverse($this->entities) as $entity_item) {
      if (!in_array($entity_item['entity_type'], $bypass_entities)) {
        $this->getCore()->entityDelete($entity_item['entity_type'], $entity_item['entity_id'], TRUE, FALSE);
      }
    }

    $this->entities = [];
    $this->users = [];
    $this->nodes = [];
  }

}
