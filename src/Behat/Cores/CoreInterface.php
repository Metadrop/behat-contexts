<?php

namespace Metadrop\Behat\Cores;

/**
 * Interface to abstract Core helper methods related with Drupal.
 */
interface CoreInterface {

  /**
   * Gets a value from Drupal's State API.
   *
   * @param string $key
   *   The key of the data to retrieve from State API.
   */
  public function getState($key);

  /**
   * Sets a value from Drupal's State API.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function setState($key, $value);

  /**
   * Clear page caches.
   *
   * @param string $path
   *   Path.
   */
  public function pageCacheClear($path);

  /**
   * Clear cache.
   *
   * @param string $cid
   *   Cid.
   * @param string $bin
   *   Cache bin.
   */
  public function cacheClear($cid, $bin = 'cache');

  /**
   * Clear an entity static cache.
   *
   * @param string $entity_type_id
   *   Entity type id to clear its static cache.
   * @param array $ids
   *   List of ids to clear its static cache. If null, all entities are cleared.
   */
  public function staticEntityCacheClear($entity_type_id, array $ids = NULL);

  /**
   * Run elysia cron.
   */
  public function runElysiaCron();

  /**
   * Force elysia cron job to be executed.
   *
   * @param string $job
   *   Elysia job name.
   */
  public function runElysiaCronJob($job);

  /**
   * Run cron for specific module.
   *
   * @param string $module_name
   *   Module name.
   */
  public function runModuleCron($module_name);

  /**
   * Run specific cron job using ultimate_cron module.
   *
   * @param string $cron_name
   *   Cron name.
   */
  public function runUltimateCron($cron_name);

  /**
   * Get user by specific property.
   *
   * @param string $property
   *   User property.
   * @param string $value
   *   Value.
   * @param string $reset
   *   Don't use cache to get user.
   *
   * @return mixed
   *   User loaded.
   */
  public function loadUserByProperty($property, $value, $reset = TRUE);

  /**
   * Obtain user roles.
   *
   * @param mixed $user
   *   User.
   */
  public function getUserRoles($user);

  /**
   * Get last entity id.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   (Optional) Entity bundle.
   *
   * @return int
   *   Entity id.
   */
  public function getLastEntityId($entity_type, $bundle = NULL);

  /**
   * Load single entity by id.
   *
   * @param string $entity_type
   *   Entity type.
   * @param int $id
   *   Entity id.
   *
   * @return mixed
   *   Entity loaded.
   */
  public function entityLoadSingle($entity_type, $id);

  /**
   * Attach a paragraph to an entity.
   *
   * @param string $paragraph_type
   *   Paragraph type.
   * @param string $paragraph_field
   *   Field in which paragraph will be inserted.
   * @param array $paragraph_values
   *   Paragraph values.
   * @param mixed $entity
   *   Entity where the paragraph will be inserted.
   * @param string $entity_type
   *   Entity type.
   */
  public function attachParagraphToEntity($paragraph_type, $paragraph_field, array $paragraph_values, $entity, $entity_type);

  /**
   * Entity save.
   *
   * @param string $entity_type
   *   Entity type.
   * @param mixed $entity
   *   Entity.
   */
  public function entitySave($entity_type, $entity);

  /**
   * Get entity uri.
   *
   * @param string $entity_type
   *   Entity type.
   * @param mixed $entity
   *   Entity.
   */
  public function entityUri($entity_type, $entity);

  /**
   * Grant node access acquirements.
   *
   * @param object $node
   *   Node.
   */
  public function nodeAccessAcquireGrants($node);

  /**
   * Get file destionation.
   *
   * @param string $filename
   *   The name of the file to get.
   * @param string $directory
   *   A string containing the files scheme, usually "public://".
   *
   * @return string|null
   *   File destination.
   */
  public function getFileDestination($filename, $directory);

  /**
   * Delete file.
   *
   * @param int $fid
   *   File id.
   */
  public function fileDelete($fid);

  /**
   * Obtain entity by field value.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $field_name
   *   Field name.
   * @param string $value
   *   Field value.
   *
   * @return object
   *   Entity.
   */
  public function getEntityByField($entity_type, $field_name, $value);

  /**
   * Obtain entity value.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $entity
   *   Entity.
   * @param string $fallback
   *   Fallback (optional).
   *
   * @return string
   *   Entity field value.
   */
  public function getEntityFieldValue($field_name, $entity, $fallback = NULL);

  /**
   * Get entity types availables.
   *
   * @return array|mixed
   *   Entity types.
   */
  public function getEntityTypes();

  /**
   * Get entities by condition.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $condition_key
   *   Condition key.
   * @param string $condition_value
   *   Condition value.
   * @param string $condition_operand
   *   Condition operand.
   *
   * @return array
   *   An array of entity ids.
   */
  public function getEntitiesWithCondition($entity_type, $condition_key, $condition_value, $condition_operand = 'LIKE');

  /**
   * Delete entities by condition.
   *
   * @param string $entity_type
   *   Entity type.
   * @param int $entity_id
   *   Entity id.
   * @param bool $reset_cache
   *   Wether the entity cache should be reset before loading it.
   */
  public function entityDelete($entity_type, $entity_id, $reset_cache = FALSE);

  /**
   * Obtain warnings and notices from watchdog logs.
   *
   * @param int $scenario_start_time
   *   Scenario start time.
   * @param array $severities
   *   Severities.
   * @param array $types
   *   Log types (php, access denied...).
   */
  public function getDbLogMessages(int $scenario_start_time, array $severities = [], array $types = []);

  /**
   * Get watchdog log grouped and count it.
   *
   * @param int $start_time
   *   Start time to get the logs.
   * @param array $severities
   *   Severities.
   * @param array $types
   *   Log types (php, access denied...).
   * @param int $log_limit
   *   The amount of logs to get, -1 to do not limit the result.
   */
  public static function getDbLogGroupedMessages(int $start_time, array $severities = [], array $types = [], int $log_limit = -1);

  /**
   * Delete a list of entities of the same entity type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param array $entities_ids
   *   Entity id list.
   * @param bool $reset_cache
   *   Wether the entity caches should be reset before loading them.
   */
  public function entityDeleteMultiple($entity_type, array $entities_ids, $reset_cache = FALSE);

  /**
   * Load an entity with a specific label.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $label
   *   Entity label.
   *
   * @return mixed
   *   Entity.
   */
  public function loadEntityByLabel(string $entity_type, string $label);

  /**
   * Load an entity by properties.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $properties
   *   The array of properties to search.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Entity found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadEntityByProperties(string $entity_type, array $properties);

  /**
   * Make string variable replacements statically.
   *
   * @param string $string
   *   Message  with variables placeholders.
   * @param array $params
   *   List of variables replacements.
   *
   * @return string
   *   Message with replacements.
   */
  public static function formatStringStatic($string, array $params);

  /**
   * Make string variable replacements.
   *
   * @param string $string
   *   Message  with variables placeholders.
   * @param array $params
   *   List of variables replacements.
   *
   * @return string
   *   Message with replacements.
   *
   * @see self::formatStringStatic()
   */
  public function formatString($string, array $params);

  /**
   * Create a url from a file.
   *
   * @param mixed $file
   *   File, in its Drupal relative data type.
   * @param bool $relative
   *   Determine if the url retrieved will be relative or absolute.
   *
   * @return string
   *   File url.
   */
  public function createFileUrl($file, bool $relative = TRUE);

  /**
   * Obtain the language prefix from label.
   *
   * @param string $language
   *   Language.
   *
   * @return string
   *   Language prefix or empty if not found.
   */
  public function getLanguagePrefix($language);

  /**
   * Builds URL string given an internal path and a langcode.
   *
   * It is needed to build a URL without knowing the specific
   * language detection the site has. Typically language detection
   * is done by language prefix, but in some cases other specific
   * logics are used.
   *
   * @param string $path
   *   Drupal internal path.
   * @param string|NULL $langcode
   *   Langcode, if needed.
   *
   * @return string
   *   Relative path accesible by browser.
   */
  public function buildPath(string $path, string $langcode = NULL);

  /**
   * Gets the request time.
   *
   * @return int
   */
  public function getRequestTime();

}
