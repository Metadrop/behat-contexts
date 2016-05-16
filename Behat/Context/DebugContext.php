<?php

/**
 * @file
 *
 * Debug Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;


use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;


use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Testwork\Tester\Result\TestResult;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;

/**
 *
 * Context to debug tests.
 *
 * This  context can save an error report on failed steps that includes:
 *   - A text file with Behat error string and exception dump.
 *   - A HTML dump of the current page.
 *   - If driver is Selenium 2, a screenshot of the current page state.
 *
 * It also provides steps to generate screenshots and save page contents to
 * file.
 *
 * Context params:
 *   'report_on_error': If yes, the context generates the report on failed
 *      steps.
 *   'error_reporting_path': Path where to store generated reports.
 *   'screenshots_path': Path where to store generated screenshots.
 *   'page_contents_path': Path where to store page contents.
 *
 *  screenshots_path is used when asked to save a screenshot, while
 *  error_reporting_path is used for error reporting. Thus, when a error report
 *  includes a screenshot it's saved int he error_reporting_path path, along
 *  the other files of the report.
 *
 */
class DebugContext extends RawDrupalContext implements SnippetAcceptingContext {

  const DEFAULT_HEIGHT = 600;

  /**
   * Context parameters.
   *
   * @var array
   */
  protected $customParams;

  /**
   * The Mink session.
   *
   * @var type Behat\Mink\Session
   */
  protected $session;

  /**
   * Step result
   *
   * @var type
   */
  protected $result;

  /**
   * Failed step.
   *
   * @var Behat\Gherkin\Node\StepNode
   */
  protected $step;

  /**
   * Failed step's feature.
   *
   * @var Behat\Gherkin\Node\FeatureNode
   */
  protected $feature;

  /**
   * Constructor.
   *
   * Save class params, if any.
   *
   * @param array $parameters
   */
  public function __construct($parameters) {

    // Default values.
    $defaults = array(
      'report_on_error' => FALSE,
      'error_reporting_path' => '/tmp',
      'screenshots_path' => '/tmp',
      'page_contents_path' => '/tmp',
    );

    // Collect received parameters.
    $this->customParameters = array();
    if (!empty($parameters)) {
      // Filter any invalid parameters.
      $this->customParameters = array_intersect_key($parameters, $defaults);
    }

    // Apply default values.
    $this->customParameters += $defaults;
  }

  /**
   * Is the reporting enabled?
   *
   * @return bool
   *   Return TRUE if reporting is enabled.
   */
  public function isReportingEnabled() {
    return $this->customParameters['report_on_error'];
  }

  /**
   * Returns the configured path where to store error reports.
   *
   * @return string
   *   Path where to store error reports.
   */
  public function getReportPath() {
    return $this->customParameters['error_reporting_path'];
  }

  /**
   * Returns the configured path where to store screenshots.
   *
   * @return string
   *   Path where to store screenshots.
   */
  public function getScreenshotsPath() {
    return $this->customParameters['screenshots_path'];
  }

  /**
   * Returns the configured path where to store page contents files.
   *
   * @return string
   *   Path where to store page contents files.
   */
  public function getPageContentPath() {
    return $this->customParameters['page_contents_path'];
  }

  /**
   * Get absolute path for a screenshot path.
   *
   * If given path is abislutem return it as is. If relative, add screenshots
   * configured path.
   *
   * @param string $path
   *   Path to turn into an absloute path.
   * @return string
   *   Screenshot absolute path.
   */
  public function getScreenshotAbsolutePath($path) {
    if ($path[0] !== '/') {
      $path = $this->getScreenshotsPath() . '/' . $path;
    }
    return $path;
  }

  /**
   * Returns the template for the files of the repor.
   *
   * All files form a report share the same name but extension is different.
   *
   * @return string
   *   Filename template.
   */
  public function getFilenameReportTemplate() {
    if (empty($this->filenameTemplate)) {
      $this->filenameTemplate = 'Error-' . date("Ymd--H-i-s") . '_' . basename($this->feature->getFile(), '.feature') . '_line_' . $this->step->getLine();
    }
    return $this->filenameTemplate;
  }

  /**
   * Generates and saves an error report.
   *
   * @param \FeatureNode $feature
   *   Gherkin feature object.
   * @param \StepNode $step
   *   Gherkin step object.
   */
  public function saveReport() {
    $this->session = $this->getSession();

    $this->saveInfoFile();

    // If it's Selenium Driver save a screenshot.
    if ($this->session ->getDriver() instanceof \Behat\Mink\Driver\Selenium2Driver) {
      $this->saveScreenshot($this->getFilenameReportTemplate() . '.png', $this->getReportPath());
    }

    // Dump HTML content.
    $error_page_filepath = $this->getReportPath() . '/' . $this->getFilenameReportTemplate() . '.html';
    file_put_contents($error_page_filepath, $this->session->getPage()->getContent());
  }

  /**
   * Generates and saves the report info file.
   *
   * Contains the current URL and the error exception dump.
   */
  protected function saveInfoFile() {
    $error_filepath = $this->getReportPath() . '/' . $this->getFilenameReportTemplate() . '.txt';

    // Generate content.
    $error_report = $this->session->getCurrentUrl() . "\n\n";
    $error_report .= $this->result->getException();

    // Save it.
    file_put_contents($error_filepath, $error_report);
  }

  /**
   * Generate a error report on failed step.
   *
   * @AfterStep
   */
  public function generateReportIfStepFailed(AfterStepScope $scope) {
    if ($this->isReportingEnabled()) {
      $this->result = $scope->getTestResult();
      $test_failed = $this->result->getResultCode() === TestResult::FAILED;
      if ($test_failed) {
        $this->feature = $scope->getFeature();
        $this->step = $scope->getStep();
        $this->saveReport();
      }
    }
  }

  /**
   * @Then capture full page with a width of :width
   */
  public function captureFullPageWithAWidthOf($width) {
    $this->saveACaptureFullPageWithWidthOfTo($width, $this->getScreenshotsPath());
  }

  /**
   * @Then capture full page with a width of :width with name :filename
   */
  public function captureFullPageWithAWidthOfWithFilename($width, $filename) {
    $this->saveACaptureFullPageWithWidthOfToWithName($width, $this->getScreenshotsPath(), $filename);
  }

  /**
   * @Then capture full page with width of :width to :path
   */
  public function captureFullPageWithWidthOfTo($width, $path) {
    $milliseconds = gettimeofday();
    $filename = 'Screenshot-' . date("Ymd--H-i-s") . '.' . $milliseconds['usec'] . '.png';
    $this->saveACaptureFullPageWithWidthOfToWithName($width, $this->getScreenshotAbsolutePath($path), $filename);
  }

  /**
   * @Then capture full page with width of :width to :path with name :filename
   */
  public function captureFullPageWithWidthOfToWithName($width, $path, $filename) {
    // Use default height as screenshot is going to capture the complete page.
    $this->getSession()->resizeWindow($width, $this::DEFAULT_HEIGHT, 'current');
    $this->savescreenShot($filename, $this->getScreenshotAbsolutePath($path));
  }

  /**
   * @Given /^save last response$/
   *
   * Step to save page content to a file.
   */
  public function saveLastResponse() {
    $this->saveLastResponseToFile($this->getPageContentPath());
  }

  /**
   * @Then save last response to :path
   *
   * Step to save page content fo a file in a given path.
   */
  public function saveLastResponseToFile($path) {
    $milliseconds = gettimeofday();
    $filename = 'PageContent-' . date("Ymd--H-i-s") . '.' . $milliseconds['usec'];
    $error_page_filepath = $path . '/' . $filename . '.html';
    file_put_contents($error_page_filepath, $this->getSession()->getPage()->getContent());
  }

  /**
   * @Then I wait for :seconds second(s)
   *
   * Wait seconds before the next step.
   *
   * @param int|string $seconds
   *   Number of seconds to wait. Must be an integer value.
   */
  public function iWaitForSeconds($seconds) {
    if (!filter_var($seconds, FILTER_VALIDATE_INT) !== false) {
      throw new \InvalidArgumentException("Expected a valid integer number of seconds but given value \"$seconds\" is invalid.");
    }
    sleep($seconds);
  }
}
