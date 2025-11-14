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
use Behat\Hook\BeforeScenario;

/**
 * Context to show test info before tests to record it.
 *
 * This context provides functionality to record videos of test scenarios.
 * It can be configured to record videos for all scenarios or only for failed ones.
 *
 * Context params:
 *   'enabled': Si es TRUE, activa la funcionalidad de grabación y visualización.
 *   'show_test_info_screen': Si es TRUE, muestra una pantalla con la información del test y los pasos.
 *   'show_test_info_screen_time': Milisegundos que se muestra la pantalla de información del test.
 *   'show_green_screen': Si es TRUE, muestra una pantalla verde antes del test.
 *   'show_green_screen_time': Milisegundos que se muestra la pantalla verde.
 *   'show_step_info_bubble': Si es TRUE, muestra una burbuja con información del paso antes de cada step.
 *   'show_step_info_bubble_time': Milisegundos que se muestra la burbuja de información del paso.
 *   'show_error_info_bubble': Si es TRUE, muestra una burbuja de error si un paso falla.
 *   'show_error_info_bubble_time': Milisegundos que se muestra la burbuja de error.
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
      'show_test_info_screen_time' => 1000,
      'show_green_screen' => FALSE,
      'show_green_screen_time' => 1000,
      'show_step_info_bubble' => TRUE,
      'show_step_info_bubble_time' => 1000,
      'show_error_info_bubble' => TRUE,
      'show_error_info_bubble_time' => 5000,
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
   * @param BeforeScenarioScope $scope
   *    Scenario scope.
   */
  #[BeforeScenario('@javascript')]
  public function showScenarioDataBeforeTest(BeforeScenarioScope $scope) {
    if (!$this->customParameters['enabled'] || !$this->isJavascriptAvailable()) {
      return;
    }

    // Show green screen if enabled.
    if ($this->customParameters['show_green_screen']) {
      $this->showGreenScreen();
    }

    // Show test info screen.
    if ($this->customParameters['show_test_info_screen']) {
      // Get the scenario and feature info.
      $scenario = $scope->getScenario();
      $feature = $scope->getFeature();
      $feature_description = $feature->getDescription() ?? '';
      $scenario_title = $scenario->getTitle() ?? '';
      $scenario_steps = $scenario->getSteps();

      $steps_texts = [];
      foreach ($scenario_steps as $step_node) {
        $step_text = $step_node->getKeyword() . " " . $step_node->getText();
        $step_text = $this->wrapQuotedTextWithStrong($step_text);
        if ($step_node->hasArguments()) {
          $step_text .= $this->getStepArgumentsHtml($step_node);
        }
        $steps_texts[] = $step_text;
      }
      $steps_html = implode('<br/>', $steps_texts);

      $background_steps = [];
      $background = $feature->getBackground();
      if ($background) {
        foreach ($background->getSteps() as $background_step) {
          $background_steps[] = $background_step->getText();
        }
      }
      $background_html = implode('<br/>', $background_steps);
      $style_html = '<style>html, body { font-family: monospace; font-size: 14px; }</style>';
      $html = $style_html;
      $html .= '<h1>' . htmlspecialchars($feature_description) . '</h1>';
      $html .= '<h2>' . htmlspecialchars($scenario_title) . '</h2>';
      if ($background_html) {
        $html .= '<h3>Background:</h3><p>' . $background_html . '</p>';
      }
      $html .= '<h3>Steps:</h3><p>' . $steps_html . '</p> <br/> <br/>';

      // Build debug page
      $this->getSession()->visit('about:blank');
      $this->getSession()->executeScript("document.documentElement.innerHTML= " . json_encode($html) . ";");
      // We make an scroll to see all the scenario info
      $this->getSession()->executeScript("
        const screen_height = window.innerHeight;
        const total_height = document.body.scrollHeight;
        const steps_count = Math.ceil(total_height / screen_height);
        const wait_time = " . (int) $this->customParameters['show_test_info_screen_time'] . "/steps_count;
        let current_step = 0;
        function scrollNext() {
          window.scrollTo(0, current_step * screen_height);
          current_step++;
          if (current_step < steps_count) {
            setTimeout(scrollNext, wait_time);
          }
        }
        scrollNext();
      ");
      // Add extra wait to ensure the last scroll is completed and there is time to see the info
      $this->getSession()->wait($this->customParameters['show_test_info_screen_time'] + 1000);
    }
  }

  /**
   * Shows step information after each step if there is an error.
   */
  #[AfterStep]
  public function showRedWindowIfError(AfterStepScope $scope) {
    if (
      $this->customParameters['enabled']
      && $this->isJavascriptAvailable()
      && $this->customParameters['show_error_info_bubble']
      && !$scope->getTestResult()->isPassed()
    ) {
      $step_html = $this->buildStepHtml($scope->getStep(), TRUE);
      $error_style = "position: fixed; top: 20px; left: 20px; background-color: rgba(255,0,0,1); color: white; padding: 15px; z-index: 999999; border-radius: 3px; font-family: monospace; font-size: 16px;";
      $this->showStepBubble($step_html, $error_style, $this->customParameters['show_error_info_bubble_time']);
    }
  }

  /**
   * Shows step information before each step.
   */
  #[BeforeStep]
  public function showStepInfo(BeforeStepScope $scope) {
    if (
      $this->customParameters['enabled'] &&
      $this->isJavascriptAvailable() &&
      $this->customParameters['show_step_info_bubble']
    ) {
      $step_html = $this->buildStepHtml($scope->getStep());
      $info_style = "position: fixed; top: 20px; left: 20px; background-color: rgba(0,201,10,1); color: white; padding: 5px; z-index: 999999; border-radius: 3px; font-family: monospace; font-size: 13px;";
      $this->showStepBubble($step_html, $info_style, $this->customParameters['show_step_info_bubble_time']);
    }
  }

  /**
   * Wraps text inside double or single quotes with <strong> tags.
   *
   * @param string $text
   *   The input text.
   *
   * @return string
   *   The text with quoted substrings wrapped in <strong>.
   */
  protected function wrapQuotedTextWithStrong($text) {
    // Match both "..." and '...'
    return preg_replace_callback(
      '/(["\'])(.+?)\1/',
      function ($matches) {
        return '<strong>' . $matches[1] . $matches[2] . $matches[1] . '</strong>';
      },
      $text
    );
  }

  /**
   * Builds the HTML for the step, optionally as error.
   */
  private function buildStepHtml($step, $is_error = FALSE) {
    $step_text = $step->getKeyword() . " " . $step->getText();
    $step_text = str_replace(
      [' ', '(', ')', '/', '@', "'", '"'],
      ['&nbsp;', '&#40;', '&#41;', '&#47;', '&#64;', '&#39;', '&quot;'],
      $step_text
    );
    if ($step->hasArguments()) {
      $step_text .= $this->getStepArgumentsHtml($step);
    }
    if ($is_error) {
      $step_text = 'Test Failed: ' . $step_text;
    }
    return $step_text;
  }

  /**
   * Returns the HTML representation of step arguments.
   *
   * @param \Behat\Gherkin\Node\StepNode $step_node
   *   The step node.
   *
   * @return string
   *   The HTML for the step arguments.
   */
  private function getStepArgumentsHtml($step_node) {
    $arguments_html = '';
    foreach ($step_node->getArguments() as $argument_node) {
      if ($argument_node instanceof \Behat\Gherkin\Node\PyStringNode) {
        $arguments_html .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;' . str_replace("\n", '<br/>&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($argument_node->getRaw()));
      }
      elseif ($argument_node instanceof \Behat\Gherkin\Node\TableNode) {
        $arguments_html .= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;' . str_replace("\n", '<br/>&nbsp;&nbsp;&nbsp;&nbsp;', $argument_node->getTableAsString());
      }
      else {
        $arguments_html .= '<br/>' . htmlspecialchars((string) $argument_node);
      }
    }
    $arguments_html = str_replace(' ', '&nbsp;', $arguments_html);
    return $arguments_html;
  }

  /**
   * Shows the step info bubble.
   */
  private function showStepBubble($html, $style, $wait_time) {
    $bubble_html = '<div id="behat-step" style="' . $style . '">' . $html . '</div>';
    $this->getSession()->executeScript("document.body.insertAdjacentHTML('afterbegin', '" . $bubble_html . "');");
    $this->getSession()->wait($wait_time);
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

  /**
   * Shows a green screen for a configured amount of time.
   */
  private function showGreenScreen() {
    $this->getSession()->visit('about:blank');
    $this->getSession()->executeScript("
      document.documentElement.style.background = '#00ff00';
      document.body.style.background = '#00ff00';
    ");
    $this->getSession()->wait($this->customParameters['show_green_screen_time']);
  }
}
