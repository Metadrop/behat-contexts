<?php

namespace Metadrop\Behat\Context;

use Behat\Step\Given;

class CronContext extends RawDrupalContext {

  /**
   * Run elysia-cron.
   */
  #[Given('I run elysia cron')]
  public function iRunElysiaCron() {
    $this->getCore()->runElysiaCron();
  }

  /**
   * Run elysia-cron-job.
   */
  #[Given('I run the elysia cron :job job')]
  public function iRunElysiaCronJob($job) {
    // @NOTE We force it
    $this->getCore()->runElysiaCronJob($job);
  }

  /**
   * Run search-api-cron
   */
  #[Given('I run the cron of Search API')]
  public function iRunTheCronOfSearchApi() {
    $this->getCore()->runModuleCron('search_api');
  }

  /**
   * Run search-api-solr-cron
   */
  #[Given('I run the cron of Search API Solr')]
  public function iRunTheCronOfSearchApiSolr() {
    $this->getCore()->runModuleCron('search_api_solr');
  }

}
