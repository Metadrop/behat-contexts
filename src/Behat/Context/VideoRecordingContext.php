<?php
/**
 * @file
 *
 * Video Recording Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;
use Behat\Hook\AfterStep;
use Behat\Hook\BeforeStep;

/**
 * Context to show test info before tests to record it.
 *
 * This context provides functionality to record videos of test scenarios.
 * It can be configured to record videos for all scenarios or only for failed ones.
 *
 * Context params:
 *   'show_test_info_screen': If true, shows a screen with tests info and steps.
 *   'show_test_info_screen_time': How many ms the info screen should be shown.
 *   'show_green_screen': If true, shows a green screen before the test.
 *   'show_green_screen_time': How many ms the green screen should be shown.
 *
 * @package Metadrop\Behat\Context
 */
class VideoRecordingContext extends RawMinkContext {

  /**
   * Context parameters.
   *
   * @var array
   */
  protected $customParameters;

  /**
   * Constructor.
   *
   * Save class params, if any.
   *
   * @param array $parameters
   *   The definition parameters.
   */
  public function __construct($parameters = []) {
    // Default values.
    $defaults = [
      'enabled' => FALSE,
      'show_test_info_screen' => TRUE,
      'show_test_info_screen_time' => 2000,
      'show_green_screen' => FALSE,
      'show_green_screen_time' => 3000,
      'show_step_info_bubble' => TRUE,
      'show_step_info_bubble_time' => 2000,
      'show_error_info_bubble' => TRUE,
      'show_error_info_bubble_time' => 2000,
    ];

    // Collect received parameters.
    $this->customParameters = [];
    if (!empty($parameters)) {
      // Filter any invalid parameters.
      $this->customParameters = array_intersect_key($parameters, $defaults);
    }

    // Apply default values.
    $this->customParameters += $defaults;
  }

  /**
   * Show tests name and green screen.
   *
   * @BeforeScenario @javascript
   *
   * @param BeforeScenarioScope $scope
   *    Scenario scope.
   */
  public function showScenarioDataBeforeTest(BeforeScenarioScope $scope) {
    if (!$this->customParameters['enabled']) {
      return;
    }

    // Show green screen.
    if ($this->customParameters['show_green_screen']) {
      // Build green split page
      $this->getSession()->visit('about:blank');
      $this->getSession()->executeScript("
        document.documentElement.style.background = '#00ff00';
        document.body.style.background = '#00ff00';
      ");
      $this->getSession()->wait($this->customParameters['show_green_screen_time']);
    }

    // Show test info screen.
    if ($this->customParameters['show_test_info_screen']) {
      // Get the scenario and feature info.
      $scenario = $scope->getScenario();
      $feature = $scope->getFeature();
      $feature_description = $feature->getDescription() ?? '';
      $scenario_name = $scenario->getTitle() ?? '';
      $steps = $scenario->getSteps();

      $steps_texts = [];
      foreach ($steps as $step) {
        $steps_texts[] = $step->getText();
      }
      $steps_string = implode('<br>', $steps_texts);

      $background_steps = [];
      $background = $feature->getBackground();
      if ($background) {
        foreach ($background->getSteps() as $step) {
          $background_steps[] = $step->getText();
        }
      }
      $background_steps = implode('<br>', $background_steps);

      // Build HTML content
      $html = '<h1>' . htmlspecialchars($feature_description) . '</h1>';
      $html .= '<h2>' . htmlspecialchars($scenario_name) . '</h2>';
      if ($background_steps) {
        $html .= '<h3>Background:</h3><p>' . $background_steps . '</p>';
      }
      $html .= '<h3>Steps:</h3><p>' . $steps_string . '</p>';

      // Build debug page
      $this->getSession()->visit('about:blank');
      $this->getSession()->executeScript("document.documentElement.innerHTML= "
        . json_encode($html) . ";");
      $this->getSession()->wait($this->customParameters['show_test_info_screen_time']);
    }
  }

  /**
   * Shows step information after each step if there is an error.
   *
   * @AfterStep
   */
  public function showRedWindowIfError(AfterStepScope $scope) {
    if (
      !$this->isJavascriptAvailable() ||
      !$this->customParameters['show_error_info_bubble'] ||
      !$this->customParameters['enabled']
    ) {
      return;
    }

    if (!$scope->getTestResult()->isPassed()) {
      $stepHtml = $this->buildStepHtml($scope->getStep(), true);
      $style = "position: fixed; bottom: 20px; left: 10px; background-color: rgba(255,0,0,1); color: white; padding: 15px; z-index: 999999; border-radius: 3px; font-family: monospace; font-size: 16px;";
      $this->showStepBubble($stepHtml, $style, $this->customParameters['show_error_info_bubble_time']);
    }
  }

  /**
   * Shows step information before each step.
   *
   * @BeforeStep
   */
  public function showStepInfo(BeforeStepScope $scope) {
    if (
      !$this->isJavascriptAvailable() ||
      !$this->customParameters['show_step_info_bubble'] ||
      !$this->customParameters['enabled']
    ) {
      return;
    }
    $stepHtml = $this->buildStepHtml($scope->getStep());
    $style = "position: fixed; bottom: 20px; left: 10px; background-color: rgba(0,100,0,1); color: white; padding: 5px; z-index: 999999; border-radius: 3px; font-family: monospace; font-size: 12px;";
    $this->showStepBubble($stepHtml, $style, $this->customParameters['show_step_info_bubble_time']);
  }

  /**
   * Builds the HTML for the step, optionally as error.
   */
  private function buildStepHtml($step, $isError = false) {
    $text = $step->getText();
    if ($step->hasArguments()) {
      foreach ($step->getArguments() as $argument) {
        $text .= '<br/>' . str_replace("\n", '<br/>', $argument->getTableAsString());
      }
    }
    $text = str_replace(' ', '&nbsp;', $text);
    if ($isError) {
      $text = 'Test Failed: ' . $text;
    }
    return $text;
  }

  /**
   * Shows the step info bubble.
   */
  private function showStepBubble($html, $style, $waitTime) {
    $divHtml = '<div id="behat-step" style="' . $style . '">' . $html . '</div>';
    $this->getSession()->executeScript("document.body.insertAdjacentHTML('afterbegin', '" . $divHtml . "');");
    $this->getSession()->wait($waitTime);
    $this->getSession()->executeScript("document.getElementById('behat-step').remove();");
  }
  
  /**
   * Check if javascript is available.
   */
  protected function isJavascriptAvailable() {
    $driver = $this->getSession()->getDriver();
    return $driver instanceof \Behat\Mink\Driver\Selenium2Driver
      || $driver instanceof \Behat\Mink\Driver\ChromeDriver
      || $driver instanceof \Behat\Mink\Driver\PantherDriver;
  }
}

