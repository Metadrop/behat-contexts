<?php

namespace Metadrop\Behat\Context;

trait StateContextTrait {

  /**
   * Keep track of any state that was changed so they can easily be reverted.
   *
   * @var array
   */
  protected $state = [];

  /**
   * Get active Drupal Driver.
   *
   * @return \Drupal\Driver\DrupalDriver
   */
  abstract public function getDriver($name = NULL);

  /**
   * Get current Drupal core.
   *
   * @return \NuvoleWeb\Drupal\Driver\Cores\CoreInterface|\Drupal\Driver\Cores\CoreInterface
   *   Drupal core object instance.
   */
  public function getCore() {
    return $this->getDriver()->getCore();
  }

  /**
   * Revert any changed config.
   *
   * @AfterScenario
   */
  public function cleanState() {
    // Revert config that was changed.
    foreach ($this->state as $name => $value) {
      $this->getCore()->stateSet($name, $value);
    }
    $this->state = [];
  }

  /**
   * Sets a state item.
   *
   * @param string $key
   *   The state key.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @Given I set the state key :key to :value
   */
  public function setState($key, $value) {
    $backup = $this->getCore()->stateGet($key);
    $this->getCore()->stateSet($key, $value);
    $this->state[$key] = $backup;
  }

}
