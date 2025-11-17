<?php

/**
 * @file
 *
 * Debug Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Testwork\Tester\Result\TestResult;
use Behat\Step\Then;
use Behat\Step\Given;
use NuvoleWeb\Drupal\DrupalExtension\Context\ScreenShotContext as NuvoleScreenshotContext;

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
 *   'error_reporting_url': Url to show generated reports.
 *   'screenshots_path': Path where to store generated screenshots.
 *   'page_contents_path': Path where to store page contents.
 *
 *  screenshots_path is used when asked to save a screenshot, while
 *  error_reporting_path is used for error reporting. Thus, when a error report
 *  includes a screenshot it's saved int he error_reporting_path path, along
 *  the other files of the report.
 *
 */
class DebugContext extends NuvoleScreenshotContext {

  const DEFAULT_HEIGHT = 600;

  /**
   * Context parameters.
   *
   * @var array
   */
  protected $customParameters;

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
   * Failed step's file name.
   *
   * @var completed file name error.
   */
  protected $filenameTemplate;

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
      'error_reporting_path' => sys_get_temp_dir(),
      'error_reporting_url' => NULL,
      'screenshots_url' => NULL,
      'screenshots_path' => sys_get_temp_dir(),
      'page_contents_path' => sys_get_temp_dir(),
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
   * Returns the configured url to show error reports.
   *
   * @return string
   *   Url to show error reports.
   */
  public function getReportUrl() {
    return $this->customParameters['error_reporting_url'];
  }

  /**
   * Returns the configured url to show error reports.
   *
   * @return string
   *   Url to show error reports.
   */
  public function getScreenshotsUrl() {
    return $this->customParameters['screenshots_url'];
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
   * If given path is absolute return it as is. If relative, add screenshots
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
   * Generates and saves an error report.
   *
   */
  public function saveReport($file, $result) {
    $this->session = $this->getSession();

    $file = str_replace([' ', '"'], ['-', ''], $file);
    $this->saveInfoFile($file, $result);
    $this->saveHtmlFile($file);
    $this->savePngFile($file);
  }

  /**
   * Generates and saves the report info file.
   *
   * Contains the current URL and the error exception dump.
   */
  protected function saveInfoFile($filename,  $result) {
    $error_file =  $filename . '.txt';
    $error_filepath = $this->getReportPath() . '/' . $error_file;

    // Generate content.
    $error_report = $this->session->getCurrentUrl() . "\n\n";
    $error_report .= $result->getException();

    // Save it.
    file_put_contents($error_filepath, $error_report);

    if ($url = $this->getReportUrl()) {
      echo " - info (exception): " . $url . '/' . $error_file  . "\n";
    }
  }

  /**
   * Generates and saves the report HTML file.
   *
   * Contains the HTML output.
   */
  public function saveHtmlFile($filename = '') {
    $error_file =  $filename . '.html';
    $error_page_filepath = $this->getReportPath() . '/' . $error_file;
    file_put_contents($error_page_filepath, $this->session->getPage()->getContent());

    if ($url = $this->getReportUrl()) {
      echo " - html (output): " . $url . '/' . $error_file  . "\n";
    }
  }

  /**
   * Generates and saves the report PNG file.
   *
   * Contains the page screenshot.
   */
  public function savePngFile($filename = '') {
    // If it's Selenium Driver save a screenshot.
    if ($this->session->getDriver() instanceof \Behat\Mink\Driver\Selenium2Driver) {
      $error_file =  $filename . '.png';
      $this->saveScreenshot($error_file, $this->getReportPath());

      if ($url = $this->getReportUrl()) {
        echo " - png (screenshot): " . $url . '/' . $error_file . "\n";
      }
    }
  }


  /**
   * @deprecated Because it is not easy to get a full page capture and it may
   * only work in Chrome. Resize viewport and do a normal capture.
   */
  #[Then('capture full page with a width of :width')]
  public function captureFullPageWithAWidthOf($width) {
    echo "\033[33m[DEPRECATED]\033[0m This step will be removed in the next major version. Use any other capture step.\n";

    $this->captureFullPageWithWidthOfToWithName($width, $this->getScreenshotsPath(), $this->generateFilenameDateBased());
  }

  /**
   * @deprecated Because it is not easy to get a full page capture and it may
   * only work in Chrome. Resize viewport and do a normal capture.
   */
  #[Then('capture full page with a width of :width with name :filename')]
  public function captureFullPageWithAWidthOfWithFilename($width, $filename) {
    echo "\033[33m[DEPRECATED]\033[0m This step will be removed in the next major version. Use any other capture step.\n";

    $this->captureFullPageWithWidthOfToWithName($width, $this->getScreenshotsPath(), $this->generateFilenameDateBased());
  }

  public function generateFilenameDateBased() {
    $milliseconds = gettimeofday();
    return 'Screenshot-' . date("Ymd--H-i-s") . '.' . $milliseconds['usec'] . '.png';
  }


  /**
   * @deprecated Because it is not easy to get a full page capture and it may
   * only work in Chrome. Resize viewport and do a normal capture.
   */
  #[Then('capture full page with a width of :width to :path')]
  public function captureFullPageWithWidthOfTo($width, $path) {

    echo "\033[33m[DEPRECATED]\033[0m This step will be removed in the next major version. Use any other capture step.\n";

   print_r($this->getScreenshotAbsolutePath($path) . "\n");
   print_r("Width: " . $width);


    $this->captureFullPageWithWidthOfToWithName($width, $this->getScreenshotAbsolutePath($path), $this->generateFilenameDateBased());
  }

  /**
   * @deprecated Because it is not easy to get a full page capture and it may
   * only work in Chrome. Resize viewport and do a normal capture.
   */
  #[Then('capture full page with a width of :width to :path with name :filename')]
  public function captureFullPageWithWidthOfToWithName($width, $filepath, $filename) {

    echo "\033[33m[DEPRECATED]\033[0m This step will be removed in the next major version. Use any other capture step.\n";

    // Use default height as screenshot is going to capture the complete page.
    $this->getSession()->resizeWindow((int) $width, $this::DEFAULT_HEIGHT, 'current');
    $message = "Screenshot created in @file_name";
    $full_path = $filepath . '/' . $filename;
    $this->createScreenshot($full_path, $message, FALSE);
  }

  /**
   * Step to save page content to a file.
   */
  #[Given('save last response')]
  public function saveLastResponse() {
    $this->createScreenshot($this->getScreenshotsPath() . DIRECTORY_SEPARATOR . 'last_response', 'File saved in @file_name');
  }

  /**
   * {@inheritdoc}
   */
  public function createScreenshot($file_name, $message, $ext = TRUE) {
    $file_path = parent::createScreenshot($file_name, $message, $ext);
    $file_base_name = basename($file_path);
    if ($url = $this->getScreenshotsUrl()) {
      print 'Screenshot url: ' . $url . DIRECTORY_SEPARATOR . $file_base_name . "\n";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createScreenshotsForErrors($file_name, $message, TestResult $result) {
    $file_base_name = basename($file_name);
    $this->saveReport($file_base_name, $result);
  }

}
