<?php

namespace Metadrop\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Use it to have Drupal context available in your context.
 */
trait DrupalContextDependencyTrait {

  /**
   * Drupal context.
   *
   * Used by contexts that depends on its methods.
   *
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  protected $drupalContext;

  /**
   * Get the Drupal context.
   *
   * It is done by searching any class that extends from DrupalContext,
   * it may be only one.
   *
   * It is assumed that the Drupal context is always present, as is the main
   * extension for behat tests in DRupal.
   *
   *
   * @param BeforeScenarioScope $scope
   *   Scope del scenario.
   */
  #[\Behat\Hook\BeforeScenario]
  public function gatherDrupalContext(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $classesArray = $environment->getContextClasses();
    foreach ($classesArray as $class_name) {
      if (is_subclass_of($class_name, DrupalContext::class) || $class_name == DrupalContext::class) {
        $this->drupalContext = $environment->getContext($class_name);
        break;
      }
    }
  }

}
