<?php

namespace Metadrop\Behat\Context;

use Drupal\Driver\Exception\BootstrapException;
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

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal version could not be determined.
   *
   * @see \Drupal\Driver\DrupalDriver::getDrupalVersion
   */
  public static function getDrupalVersion() {
    if (!isset(static::$coreVersion)) {
      // Support 6, 7 and 8.
      $version_constant_paths = [
        // Drupal 6.
        '/modules/system/system.module',
        // Drupal 7.
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/autoload.php',
        '/core/includes/bootstrap.inc',
      ];

      if (DRUPAL_ROOT === FALSE) {
        throw new BootstrapException('`drupal_root` parameter must be defined.');
      }

      foreach ($version_constant_paths as $path) {
        if (file_exists(DRUPAL_ROOT . $path)) {
          require_once DRUPAL_ROOT . $path;
        }
      }
      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }
      else {
        throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, and 8.');
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        static::$coreVersion = (integer) $version_parts[0] < 8 ? $version_parts[0] : 8;
      }
      else {
        throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }
    return static::$coreVersion;
  }

}
