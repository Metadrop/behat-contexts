<?php

namespace Metadrop\Behat\Cores\Traits;

/**
 * Entity common methods.
 */
trait EntityTrait {

  /**
   * Build a entity uri.
   *
   * @param $entity_type
   *   Entity type.
   * @param string|null $bundle
   *   Bundle (optional).
   * @param string|null $subpath
   *   Sub path (optional.
   *
   * @return string|null
   *   Path of the last entity, if exists.
   *
   * @throws \Exception
   *   When the entity does not exists it throws an exception.
   */
  public function buildEntityUri($entity_type, $entity, $subpath = NULL) {
    if (!empty($entity)) {
      $uri = $this->entityUri($entity_type, $entity);
      if (!empty($subpath)) {
        $uri .= '/' . $subpath;
      }
    }
    else {
      $uri = NULL;
    }
    return $uri;
  }

}
