<?php

namespace Metadrop\Behat\Context;

class CacheContext extends RawDrupalContext {

  /**
   * Flush page cache.
   *
   * @param string $path
   *  Page name without first "/"
   *  Use "*" as wildcard. Example: articles/*
   *
   * @Given :path page cache is flushed
   */
  public function pageCacheIsFlushed($path = NULL) {
    global $base_url;

    if (!empty($path) && $path !== '*') {
      $path = $base_url . '/' . $path;
    }

    $this->getCore()->cacheClear($path, 'page');
  }

  /**
   * Flush views data cache.
   *
   * @param string $view_name
   *  Views name
   *
   * @Given :view view data cache is flushed
   */
  public function viewDataCacheIsFlushed($view_name) {
    $this->getCore()->viewsCacheClear($view_name);
  }

}
