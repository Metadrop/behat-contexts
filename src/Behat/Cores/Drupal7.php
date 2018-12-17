<?php

namespace Metadrop\Behat\Cores;

use NuvoleWeb\Drupal\Driver\Cores\Drupal7 as OriginalDrupal7;
use Metadrop\Behat\Cores\Traits\UsersTrait;
use Webmozart\Assert\Assert;
use Metadrop\Behat\Cores\Traits\CronTrait;

class Drupal7 extends OriginalDrupal7 implements CoreInterface {

  use UsersTrait;
  use CronTrait;

  /**
   * {@inheritdoc}
   */
  public function cacheClear($cid, $bin = 'cache') {
    cache_clear_all($cid, $bin, TRUE);
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
  public function loadUserByProperty($property, $value, $reset = TRUE) {
    $query = db_select('users');
    $query->fields('users', array('uid'));
    $query->condition($property, $value);

    $result = $query->execute();
    $uid    = $result->fetchField();

    $account = user_load($uid, $reset);
    return $account;
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

    if (empty($id)){
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

}
