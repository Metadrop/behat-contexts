<?php

namespace Metadrop\Behat\Cores;

use Metadrop\Behat\Cores\Traits\EntityTrait;
use NuvoleWeb\Drupal\Driver\Cores\Drupal7 as OriginalDrupal7;
use Metadrop\Behat\Cores\Traits\UsersTrait;
use Webmozart\Assert\Assert;
use Metadrop\Behat\Cores\Traits\CronTrait;
use Metadrop\Behat\Cores\Traits\FileTrait;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Class Drupal7.
 */
class Drupal7 extends OriginalDrupal7 implements CoreInterface {

  use UsersTrait;
  use CronTrait;
  use FileTrait;
  use EntityTrait;

  /**
   * {@inheritdoc}
   */
  public function pageCacheClear($path) {
    $this->cacheClear($path, 'cache_page');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($cid, $bin = 'cache') {
    cache_clear_all($cid, $bin, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function staticEntityCacheClear($entity_type_id, array $ids = NULL) {
    entity_get_controller($entity_type_id)->resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsCacheClear($view_name) {
    $this->cacheClear($view_name . ':', 'cache_views-data');
  }

  /**
   * {@inheritdoc}
   */
  public function runElysiaCron() {
    elysia_cron_run(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function runElysiaCronJob($job) {
    elysia_cron_run_job($job, TRUE, TRUE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function runUltimateCron($cron_name) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserByProperty($property, $value, $reset = TRUE) {
    $query = db_select('users');
    $query->fields('users', ['uid']);
    $query->condition($property, $value);

    $result = $query->execute();
    $uid = $result->fetchField();

    return !empty($uid) ? user_load($uid, $reset) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function entityId($entity_type, $entity) {
    list($entity_id) = entity_extract_ids($entity_type, $entity);
    return $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserRoles($user) {
    return $user->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastEntityId($entity_type, $bundle = NULL) {

    $info = entity_get_info($entity_type);
    $id_key = $info['entity keys']['id'];

    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    if ($bundle) {
      $query->entityCondition('bundle', $bundle);
    }

    $query->propertyOrderBy($id_key, 'DESC');
    $query->range(0, 1);
    $query->addMetaData('account', user_load(1));

    $result = $query->execute();
    $keys = array_keys($result[$entity_type]);
    $id = reset($keys);

    if (empty($id)) {
      throw new \Exception("Can't take last one");
    }

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function entityLoadSingle($entity_type, $id) {
    $entity = entity_load_single($entity_type, $id);
    Assert::notEq($entity, FALSE, 'Entity with id "' . $id . '" exists.');
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function attachParagraphToEntity($paragraph_type, $paragraph_field, array $paragraph_values, $entity, $entity_type) {
    $paragraph_object = new ParagraphsItemEntity($paragraph_values += [
      'field_name' => $paragraph_field,
      'bundle' => $paragraph_type,
    ]);

    $paragraph_object->is_new = TRUE;
    $paragraph_object->setHostEntity($entity_type, $entity);
    $paragraph_object->save();
    return $paragraph_object;
  }

  /**
   * {@inheritdoc}
   */
  public function entitySave($entity_type, $entity) {
    entity_save($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function entityUri($entity_type, $entity) {
    $uri = entity_uri($entity_type, $entity);
    return !empty($uri['path']) ? $uri['path'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeAccessAcquireGrants($node) {
    node_access_acquire_grants($node);
  }

  /**
   * {@inheritdoc}
   */
  public function getFileDestination($filename, $directory) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function fileDelete($fid) {
    $file = file_load($fid);
    if ($file) {
      file_delete($file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfig($name) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByField($entity_type, $field_name, $value) {
    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type)
      ->propertyCondition($field_name, $value)
      ->entityOrderBy('entity_id', 'DESC')
      ->range(0, 1);

    $result = $query->execute();
    $entity_wrapper = NULL;
    if (!empty($result)) {
      $user = reset($result);
      $entity_id = array_keys($user)[0];
      $entity_wrapper = entity_metadata_wrapper($entity_type, $entity_id);
    }

    return $entity_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldValue($field_name, $entity, $fallback = NULL) {
    if (($entity instanceof \EntityMetadataWrapper || $entity instanceof \EntityStructureWrapper) && isset($entity->{$field_name})) {
      $value = ($field_name == 'roles') ? $entity->value()->{$field_name} : $entity->{$field_name}->value();
    }
    else {
      $value = $fallback;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return array_keys(entity_get_info());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesWithCondition($entity_type, $condition_key, $condition_value, $condition_operand = 'LIKE') {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, $entity_id) {
    return entity_delete($entity_type, $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDeleteMultiple($entity_type, array $entities_ids) {
    return entity_delete_multiple($entity_type, $entities_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getDbLogMessages(int $scenario_start_time, array $severities = [], array $types = []) {
    $query = db_select('watchdog', 'w')
      ->fields('w', ['message', 'variables', 'type', 'wid'])
      ->condition('timestamp', $scenario_start_time, '>=');

    if (!empty($severities)) {
      $query->condition('severity', $severities, 'IN');
    }

    if (!empty($types)) {
      $query->condition('type', $types, 'IN');
    }

    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByLabel(string $entity_type, string $label) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByProperties(string $entity_type, array $properties) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function formatString($string, array $params) {
    return format_string($string, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getState($key) {
    throw new \Exception('State API does not exists in Drupal 7. This method is supported only in Drupal 8 or greater.');
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    throw new \Exception('State API does not exists in Drupal 7. This method is supported only in Drupal 8 or greater.');
  }

  /**
   * {@inheritdoc}
   */
  public function validMail($email_address) {
    return valid_email_address($email_address);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFileScheme() {
    return file_default_scheme() . '://';
  }

  /**
   * Gets the current Honeypot time limit
   */
  public function getHoneypotLimit() {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * Sets the Honeypot time limit
   *
   * @param int $time_limit
   *   The time limit to be set.
   */
  public function setHoneypotLimit(int $time_limit) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

  /**
   * {@inheritdoc}
   */
  public function createFileUrl($file, bool $relative = TRUE) {
    throw new PendingException('Pending to implement method in Drupal 7');
  }

}
