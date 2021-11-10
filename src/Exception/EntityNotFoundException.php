<?php

namespace Metadrop\Exception;

/**
 * Exception when a entity is not found.
 */
class EntityNotFoundException extends \RuntimeException {

  /**
   * {@inheritdoc}
   */
  public function __construct(string $entity_type, $entity_id) {
    return parent::__construct(sprintf('Entity "%s" with is "%s" does not exists.', $entity_type, $entity_id));
  }

}
