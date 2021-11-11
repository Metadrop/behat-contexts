<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;
use Behat\Gherkin\Node\StepNode;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

class WaitingContext extends RawMinkContext {

  /**
   * Waiting steps.
   *
   * @var array
   */
  protected $waitingSteps = [];

  /**
   * Previous step.
   *
   * @var \Behat\Gherkin\Node\StepNode
   */
  private $previousStep;

  /**
   * Constructor.
   *
   * Save class params, if any.
   *
   * @param array $parameters
   *   The definition parameters.
   */
  public function __construct($parameters = []) {
    if (isset($parameters['waiting_steps'])) {
      $this->waitingSteps = $parameters['waiting_steps'];
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * The step "I wait for AJAX to finish at least :seconds seconds" deprecated
   * because the meaning is wrong (this step waits :seconds AT MOST, not at
   * least).
   *
   * @param int $seconds
   *   Max time to wait for AJAX.
   *
   * @Given I wait for AJAX to finish at least :seconds seconds
   * @Given I wait for AJAX to finish :seconds seconds at most
   *
   * @throws \Exception
   *   Ajax call didn't finish on time.
   */
  public function iWaitForAjaxToFinish($seconds) {
    $finished = $this->getSession()->wait($seconds * 1000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$finished) {
      throw new \Exception("Ajax call didn't finished within $seconds seconds.");
    }
  }

  /**
   * Wait for batch process.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 30 seconds (30,000 ms).
   *
   * The step "I wait for the batch job to finish at least :seconds seconds"
   * deprecated because the meaning is wrong (this step waits :seconds AT MOST,
   * not at least).
   *
   * @param init $seconds
   *
   * @Given I wait for the batch job to finish
   * @Given I wait for the batch job to finish at least :seconds seconds
   * @Given I wait for the batch job to finish :seconds seconds at most
   */
  public function iWaitForTheBatchJobToFinish($seconds = 30) {
    $this->getSession()->wait($seconds * 1000, 'jQuery("#updateprogress").length === 0');
  }

  /**
   * @Then I wait for :seconds second(s)
   *
   * Wait seconds before the next step. Usually, this step should be avoided
   * because it's not a good idea to depend on time for a step. If the system
   * needs for whatever reason more time, the step will fail, and if previous
   * action (the one we are waiting for) is completed soon we end with a test
   * that needs more time that the time really needed. Try yo use a step that
   * waits for a condition instead (although this is not always possible).
   *
   * @param int|string $seconds
   *   Number of seconds to wait. Must be an integer value.
   */
  public function iWaitForSeconds($seconds) {
    if (!filter_var($seconds, FILTER_VALIDATE_INT) !== FALSE) {
      throw new \InvalidArgumentException("Expected a valid integer number of seconds but given value \"$seconds\" is invalid.");
    }
    sleep($seconds);
  }

  /**
   * Implements after step.
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $scope
   *   After step scope.
   *
   * @AfterStep
   */
  public function afterStep(AfterStepScope $scope) {
    $this->previousStep = $scope->getStep();
  }

  /**
   * Implements before step.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeStepScope $scope
   *   Before step scope.
   *
   * @BeforeStep
   */
  public function beforeStep(BeforeStepScope $scope) {
    if ($this->previousStep instanceof StepNode) {
      $text = $this->previousStep->getText();
      foreach ($this->waitingSteps as $step => $seconds) {
        if (preg_match("/$step/i", $text)) {
          sleep($seconds);
        }
      }
    }
  }

  /**
   * Implements after scenario.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   After scenario scope.
   *
   * @AfterScenario
   */
  public function afterScenario(AfterScenarioScope $scope) {
    $this->previousStep = NULL;
  }

}
