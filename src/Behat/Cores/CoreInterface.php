<?php

namespace Metadrop\Behat\Cores;

/**
 * Class CoreInterface.
 */
interface CoreInterface {

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
   * @param string $filename
   *   The name of the file to get.
   * @param string $directory
   *   A string containing the files scheme, usually "public://".
   *
   * @return string|null
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
   * @return \stdClass
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
   * Delete entities by condition.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $condition_key
   *   Condition key.
   * @param string $condition_value
   *   Condition value.
   * @param string $condition_operand
   *   Condition operand.
   */
  public function deleteEntities($entity_type, $condition_key, $condition_value, $condition_operand = 'LIKE');

   /**
   * Delete entities by condition.
   *
   * @param string $type
   *   Entity type.
   * @param int $id
   *   Entity id.
   */
  public function entityDelete($entity_type, $entity_id);

  /**
   * Obtain the URL to the dblog event summary.
   *
   * @param int $wid
   *   Watchdog id.
   * @return string
   *   URL.
   */
  public function getDblogEventUrl(int $wid);

  /**
   * Obtain warnings and notices from watchdog logs.
   *
   * @param int $scenario_start_time
   *   Scenario start time.
   * @param bool $show_all
   *   TRUE or FALSE, if $show_all is FALSE it will show
   *   only php warnings and notices.
   */
  public function getDbLogMessages(int $scenario_start_time, bool $show_all);

}
