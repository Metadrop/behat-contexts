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

  /**
   * Dispatch scope hooks.
   *
   * @param string $scope
   *   The entity scope to dispatch.
   * @param \stdClass $entity
   *   The entity.
   */
  protected function dispatchHooks($scopeType, \stdClass $entity, $entity_type = NULL) {
    $fullScopeClass = 'Drupal\\DrupalExtension\\Hook\\Scope\\' . $scopeType;
    if (!class_exists($fullScopeClass)) {
      $fullScopeClass = 'Metadrop\\Behat\\Hook\\Scope\\' . $scopeType;
    }

    $scope = new $fullScopeClass($this->getDrupal()->getEnvironment(), $this, $entity);
    if (!empty($entity_type) && method_exists($fullScopeClass, 'setEntityType')) {
      $scope->setEntityType($entity_type);
    }
    $callResults = $this->dispatcher->dispatchScopeHooks($scope);

    // The dispatcher suppresses exceptions, throw them here if there are any.
    foreach ($callResults as $result) {
      if ($result->hasException()) {
        $exception = $result->getException();
        throw $exception;
      }
    }
  }

}
