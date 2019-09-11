<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext as NuvoleRawDrupalContext;

/**
 * Base context class.
 */
abstract class RawDrupalContext extends NuvoleRawDrupalContext {

  /**
   * Overrides \Drupal\Driver\Cores\AbstractCore::expandEntityFields method.
   *
   * That method is protected and we can't use it from this context.
   */
  protected function expandEntityFields($entity_type, \stdClass $entity) {
    $field_types = $this->getCore()->getEntityFieldTypes($entity_type);
    foreach ($field_types as $field_name => $type) {
      if (isset($entity->$field_name)) {
        $entity->$field_name = $this->getCore()->getFieldHandler($entity, $entity_type, $field_name)
          ->expand($entity->$field_name);
      }
    }
  }

}
