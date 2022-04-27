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
   * @param string|null $entity
   *   Bundle (optional).
   * @param string $route
   *   Entity route (optional).
   *
   * @return string|null
   *   Path of the last entity, if exists.
   *
   * @throws \Exception
   *   When the entity does not exists it throws an exception.
   */
  public function buildEntityUri($entity_type, $entity, $route = 'canonical') {
    if (!empty($entity)) {
      $uri = $this->entityUri($entity_type, $entity, $route);
    }
    else {
      $uri = NULL;
    }
    return $uri;
  }

}
