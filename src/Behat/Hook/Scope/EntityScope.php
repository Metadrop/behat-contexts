<?php

namespace Metadrop\Behat\Hook\Scope;

use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;

abstract class EntityScope extends BaseEntityScope
{

  protected $entityType;

  /**
   * Get the entity type.
   *
   * @return mixed
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Set the entity type.
   *
   * @param mixed $entityType
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

}