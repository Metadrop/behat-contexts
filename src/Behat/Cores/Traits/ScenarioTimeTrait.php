<?php

namespace Metadrop\Behat\Cores\Traits;

/**
 * Trait ScenarioTimeTrait.
 */
trait ScenarioTimeTrait {

  /**
   * Scenario start time.
   *
   * @var int
   *   This property will store the time when a scenario started.
   */
  protected $scenarioStartTime;

  /**
   * Set scenario time before a scenario starts.
   *
   * @BeforeScenario
   */
  public function setScenarioStartTimestamp() {
    $this->scenarioStartTime = time();
  }

  /**
   * Obtain scenario start time.
   *
   * @return int
   *   Start time.
   */
  public function getScenarioStartTime(): int {
    return $this->scenarioStartTime;
  }
}
