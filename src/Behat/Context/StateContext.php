<?php

namespace Metadrop\Behat\Context;

/**
 * Class StateContext.
 *
 * @package Metadrop\Behat\Context
 */
class StateContext extends RawDrupalContext {

  /**
   * Keep track of any state that was changed so they can easily be reverted.
   *
   * @var array
   */
  protected $state = [];

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