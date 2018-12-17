<?php

namespace Metadrop\Behat\Cores;

use NuvoleWeb\Drupal\Driver\Cores\Drupal7 as OriginalDrupal7;
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

}
