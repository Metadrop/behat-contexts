<?php

namespace Metadrop\Behat\Cores\Traits;

/**
 * Trait CronTrait.
 */
trait CronTrait {

  /**
   * Run specific cron.
   */
  public function runModuleCron($module_name) {
    if (function_exists($module_name . '_cron')) {
      call_user_func($module_name . '_cron');
    }
    else {
      throw new \Exception('Module "' . $module_name . '" does not have cron implemented!');
    }
  }

}
