<?php
/**
 * @file
 *
 * Video Recording Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;


/**
 * Context to show test info before tests to record it.
 *
 * This context provides functionality to record videos of test scenarios.
 * It can be configured to record videos for all scenarios or only for failed ones.
 *
 * Context params:
 *   'show_test_info_screen': If true, shows an screen with tests info and steps.
 *   'show_test_info_screen_time': How many ms must the info screen be shown.
 *   'show_green_screen': If true, shows a green screen before the test.
 *   'show_green_screen_time': How many ms must the green screen be shown.
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
      'show_test_info_screen' => TRUE,
      'show_test_info_screen_time' => 2000,
      'show_green_screen' => FALSE,
      'show_green_screen_time' => 3000,
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
   *    Scenary scope.
   */
  public function showScenarioDataBeforeTest(BeforeScenarioScope $scope) {
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
}
