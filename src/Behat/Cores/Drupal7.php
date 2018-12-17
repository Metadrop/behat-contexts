<?php

namespace Metadrop\Behat\Cores;

use NuvoleWeb\Drupal\Driver\Cores\Drupal7 as OriginalDrupal7;
use Webmozart\Assert\Assert;

class Drupal7 extends OriginalDrupal7 implements CoreInterface {

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
}
