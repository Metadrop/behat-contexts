<?php

namespace Metadrop\Behat\Hook\Scope;

use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;

/**
 * Entity scope.
 */
abstract class EntityScope extends BaseEntityScope {

  protected $entityType;

  /**
   * Get the entity type.
   *
   * @return mixed
   *   Entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Set the entity type.
   *
   * @param mixed $entityType
   *   Entity type.
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

}
