<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\DrupalExtension\Hook\Scope\AfterUserCreateScope;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Drupal\DrupalExtension\Hook\Call\AfterNodeCreate;
use Drupal\DrupalExtension\Hook\Call\AfterUserCreate;

/**
 * Class EntityContext.
 */
class EntityContext extends RawDrupalContext {

  use DrupalContextDependencyTrait;

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
   */
  #[\Behat\Step\Given('I go to the last entity :entity created')]
  #[\Behat\Step\Given('I go to the last entity :entity with :bundle bundle created')]
  #[\Behat\Step\Given('I go to :subpath of the last entity :entity created')]
  #[\Behat\Step\Given('I go to :subpath of the last entity :entity with :bundle bundle created')]
  public function goToTheLastEntityCreated($entity_type, $bundle = NULL, $subpath = NULL) {
    $last_entity = $this->getCore()->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = $this->getCore()->entityLoadSingle($entity_type, $last_entity);
    $this->visitEntityPath($entity_type, $entity, $subpath);
  }

  /**
   * Go to a specific path of an entity with a specific label.
   */
  #[\Behat\Step\Given('I go to the :entity_type entity with label :label')]
  #[\Behat\Step\Given('I go to the :entity_type entity with label :label in :language language')]
  #[\Behat\Step\Given('I go to :subpath of the :entity_type entity with label :label')]
  #[\Behat\Step\Given('I go to :subpath of the :entity_type entity with label :label in :language language')]
  public function goToTheEntityWithLabel($entity_type, $label, $subpath = NULL, $language = NULL) {
    $entity = $this->getCore()->loadEntityByLabel($entity_type, $label);
    $this->visitEntityPath($entity_type, $entity, $subpath, $language);
  }

  /**
   * Go to a specific path of an entity with an specific properties.
   */
  #[\Behat\Step\Given('I go to the :entity_type entity with properties:')]
  #[\Behat\Step\Given('I go to :subpath of the :entity_type entity with properties:')]
  #[\Behat\Step\Given('I go to the :entity_type entity in :language language with properties:')]
  public function goToTheEntityWithProperties($entity_type, TableNode $properties, $subpath = NULL, $language = NULL) {
    $properties_filter = [];
    foreach ($properties->getHash()[0] as $property_name => $property_value) {
      $properties_filter[$property_name] = $property_value;
    }
    $entity = $this->getCore()->loadEntityByProperties($entity_type, $properties_filter);
    $this->visitEntityPath($entity_type, $entity, $subpath, $language);
  }

  /**
   * Visit a path of a specific entity.
   *
   * @param string $entity_type
   *   Entity type.
   * @param mixed $entity
   *   Entity.
   * @param string|NULL $subpath
   *   Subpath.
   * @param $language
   *   Language.
   */
  protected function visitEntityPath(string $entity_type, $entity, string $subpath = NULL, $language = NULL) {
    $path_found = $this->getCore()->buildEntityUri($entity_type, $entity, $subpath);
    if (!empty($path_found)) {
      $path = $this->getCore()->buildPath(
        '/' . $path_found,
        $language,
      );
      $this->visitPath($path);
    }
    else {
      throw new \Exception("Error: Entity or path not found");
    }
  }

  /**
   * Delete the last entity created.
   */
  #[\Behat\Step\Given('last entity :entity created is deleted')]
  #[\Behat\Step\Given('last entity :entity with :bundle bundle created is deleted')]
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
   */
  #[\Behat\Step\Then('the :entity_type with field :field_name and value :value should not have the following values:')]
  public function checkDonotHaveValues($entity_type, $field_name, $value, TableNode $values) {
    $this->checkEntityTestValues($entity_type, $field_name, $value, $values, FALSE);
  }

  #[\Behat\Step\Then('the :entity_type with field :field_name and value :value translation :langcode should have the following values:')]
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

  #[\Behat\Step\Then('the :entity_type with field :field_name and value :value translation :langcode should not have the following values:')]
  public function checkEntityTranslationNotValues($entity_type, $field_name, $value, $langcode, TableNode $values) {
    $this->checkEntityTranslationValues($entity_type, $field_name, $value, $langcode, $values, FALSE);
  }

  #[\Behat\Step\Then('the :entity_type with field :field_name and value :value should not have :langcode translation')]
  public function checkEntityTranslationNotExists($entity_type, $field_name, $value, $langcode) {
    $this->checkEntityTranslationExists($entity_type, $field_name, $value, $langcode, FALSE);
  }

  #[\Behat\Step\Then('the :entity_type with field :field_name and value :value should have :langcode translation')]
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
   * Example:
   * And the 'user' with field 'mail' and value 'behat@metadrop.net' should have the following values:
   *  | mail                | uid                                                 |
   *  | behat@metadrop.net  | entity-replacement:user:mail:behat@metadrop.net:uid |
   */
  #[\Behat\Step\Then('the :object_type with field :field_name and value :value should have the following values:')]
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
   * Example:
   * And the 'Lorem ipsum sit amet' node should have the following values:
   *  | field_bar   | field_foo  |
   *  | Lorem       | ipsum      |
   */
  #[\Behat\Step\Then('the :label :entity_type should have the following values:')]
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
   * Make replacement on given string when this one contains entity-replacement.
   *
   * [entity-replacement:{entity_type}:{field_name}:{field_value}:{destiny_replacement_field_name}]
   *
   * entity_type -> the entity type on which to search.
   * field_name -> the entity field to search on.
   * field_value -> the value of the field that the entity should have.
   * destiny_replacement_field_name -> The name of the field on which to take the value to be used as a replacement.
   *
   * Examples:
   *  - [entity-replacement:user:mail:behat@metadrop.net:uid]
   *    Output: 123
   *  - [entity-replacement:user:mail:behat@metadrop.net:uid], [entity-replacement:user:mail:behat_2@metadrop.net:uid], [entity-replacement:user:mail:behat_3@metadrop.net:uid]
   *    Output: 123, 124, 125
   *  - [entity-replacement:user:mail:behat@metadrop.net:uid] - [entity-replacement:user:mail:behat_2@metadrop.net:uid] - [entity-replacement:user:mail:behat_3@metadrop.net:uid]
   *    Output: 123 - 124 - 125
   *  - entity-replacement:user:mail:behat@metadrop.net:uid (This way can only be use for single replacement)
   *    Output: 123
   *
   * Every token will be replaced, and the text between them will
   * remain the same.
   *
   * @param string $value
   *   String that contains a single or multiple entity-replacement tokens.
   *
   * @return string|bool
   *   Replaced or untouched string or FALSE otherwise.
   *
   * @throws \Exception
   */
  protected function entityTokensReplacements($value) {
    $entity_tokens = [];
    $replacements = [];
    if (strpos($value, 'entity-replacement') === 0) {
      $entity_tokens = (array) $value;
    }
    else {
      $matches = [];
      preg_match_all('#\[entity-replacement:?([^]]*)\]#', $value, $matches);
      $entity_tokens = reset($matches);
    }

    if (empty($entity_tokens)) {
      return FALSE;
    }

    // Get entity type list.
    $entity_types = $this->getCore()->getEntityTypes();

    foreach ($entity_tokens as $entity_token) {
      $token_pieces = explode(':', str_replace(['[', ']'], ['', ''], $entity_token));
      array_shift($token_pieces);
      if (count($token_pieces) < 4) {
        throw new \Exception(sprintf('Missing arguments to find the entity token with name: %s', $entity_token));
      }

      list($entity_type, $field_key, $field_value, $destiny_replacement) = $token_pieces;

      if (!in_array($entity_type, $entity_types)) {
        throw new \Exception(sprintf('The "%s" token or its entity values are not valid!', $entity_token));
      }

      $entity = $this->getCore()->getEntityByField($entity_type, $field_key, $field_value);
      $replacements[] = !empty($entity) ? $this->getCore()->getEntityFieldValue($destiny_replacement, $entity, $entity_token) : $entity_token;
    }

    return str_replace($entity_tokens, $replacements, $value);
  }

  /**
   * Replacement tokens.
   *
   * @param array|mixed $values
   *   Fields to replace.
   */
  protected function replaceTokens($values) {

    $entity_types = $this->getCore()->getEntityTypes();
    foreach ($values as $key => &$value) {
      if (($replacement = $this->entityTokensReplacements($value)) !== FALSE) {
        $value = $replacement;
      }
      elseif (strtok($value, ':') == 'relative-date' && ($relative_date = strtok(':')) !== FALSE) {
        $timestamp = strtotime($relative_date);
        // Get the rest of the string, not only string separated by ":",
        // This way we make sure if the format is something like "Y:m:d"
        // it won't be cut by ":".
        $format = strtok('');
        $value = $format === FALSE ? $timestamp : \date($format, $timestamp);
      }
    }

    return $values;
  }

  /**
   * Record time before scenario purge entities.
   */
  #[\Behat\Hook\BeforeScenario('@purgeEntities')]
  public function recordTimeBeforeScenario() {
    if ($this->getDriver() instanceof BlackboxDriver) {
      throw new UnsupportedDriverActionException('No ability to purge entities, put @api in your scenario.', $this->getDriver());
    }

    $this->timeBeforeScenario = $this->getCore()->getRequestTime();
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
   */
  #[\Behat\Hook\AfterScenario('@purgeEntities')]
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

    $map['user'] = array_map(function ($user) {
      return $user->uid;
    }, $this->users);
    $map['node'] = array_map(function ($node) {
      return $node->nid;
    }, $this->nodes);

    return $map;
  }

  /**
   * Create entities.
   */
  #[\Behat\Step\Given(':entity_type entity:')]
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
    $entity_values = $entity->toArray();
    // Place a generic id key for easier access to the value, since in the most
    // cases we only need the id in the AfterEntityCreateScope context in order
    // to load the entity.
    $entity_values['id'] = $entity->id();

    $this->dispatchHooks('AfterEntityCreateScope', (object) $entity_values, $entity_type);
    $this->entities[] = [
      'entity_type' => $entity_type,
      'entity_id' => $entity_values['id'],
    ];

    return $saved;
  }

  /**
   * @afterNodeCreate
   */
  public function afterNodeCreate(AfterNodeCreateScope $scope) {

    $node = $scope->getEntity();
    $this->nodes[] = $node;
  }

  /**
   * @afterUserCreate
   */
  public function afterUserCreate(AfterUserCreateScope $scope) {
    $user = $scope->getEntity();
    $this->users[] = $user;
  }

  #[\Behat\Hook\AfterScenario]
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

  /**
   * Check current user is not able to perform a specific operation in the site.
   */
  #[\Behat\Step\Then('I am able to :operation the :entity_type entity with label :entity_label')]
  public function iAmAbleToDoOperationAtEntityWithLabel($operation, $entity_type, $entity_label) {
    if (!$this->userHasAccessToEntity($operation, $entity_type, $entity_label)) {
      throw new \InvalidArgumentException(sprintf('User is not able to "%s" the "%s" entity with label "%s"', $operation, $entity_type, $entity_label));
    }
  }

  /**
   * Check current user is not able to perform a specific operation in the site.
   */
  #[\Behat\Step\Then('I am not able to :operation the :entity_type entity with label :entity_label')]
  public function iAmNotAbleToDoOperationAtEntityWithLabel($operation, $entity_type, $entity_label) {
    if ($this->userHasAccessToEntity($operation, $entity_type, $entity_label)) {
      throw new \InvalidArgumentException(sprintf('User is able to "%s" the "%s" entity with label "%s"', $operation, $entity_type, $entity_label));
    }
  }

  /**
   * Check if current user has access to operation on specific entity.
   *
   * @param string $operation
   *    Operation that wants to be checked. Examples: view, update, delete.
   * @param string $entity_type
   *    Entity type.
   * @param string $entity_label
   *    Entity label.
   */
  public function userHasAccessToEntity($operation, $entity_type, $entity_label) {
    $current_user_raw = $this->drupalContext->getUserManager()->getCurrentUser();
    $current_user_uid = !empty($current_user_raw) ? (int) $current_user_raw->uid : 0;
    $current_user = $this->getCore()->loadEntityByProperties('user', [
      'uid' => $current_user_uid
    ]);
    $entity = $this->getCore()->loadEntityByLabel($entity_type, $entity_label);
    if (!$entity instanceof EntityInterface) {
      throw new \InvalidArgumentException(sprintf('The "%s" entity with label "%s"', $entity_type, $entity_label));
    }

    return $entity->access($operation, $current_user);
  }

}
