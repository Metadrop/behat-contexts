<?php

namespace Metadrop\Behat\Context;

use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Drupal\Core\Url;
use Drupal\Driver\Cores\Drupal7;
use Drupal\Driver\Exception\BootstrapException;
use Metadrop\Behat\Cores\Traits\ScenarioTimeTrait;
use Behat\Testwork\Tester\Result\TestResults;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Context used to work with logs.
 *
 * @package Metadrop\Behat\Context
 */
class LogsContext extends RawDrupalContext {

  use ScenarioTimeTrait;

  /**
   * Tests base url.
   *
   * @var string|null
   */
  protected $baseUrl;

  /**
   * Log types.
   *
   * @var array
   */
  protected static $logTypes = ['php'];

  /**
   * Log levels to register.
   *
   * @var array
   */
  protected static $logLevels = [
    'ERROR' => 3,
    'WARNING' => 4,
    'NOTICE' => 5,
  ];

  /**
   * Drupal Helper Core class.
   *
   * @var string
   */
  protected static $core;

  /**
   * Suite start time.
   *
   * @var string
   */
  protected static $suiteStartTime;

  /**
   * Drupal core version.
   *
   * @var int
   */
  protected static $coreVersion;

  /**
   * LogsContext constructor.
   *
   * @param array $parameters
   *   Parameters (optional).
   */
  public function __construct(array $parameters = []) {

    if (isset($parameters['base_url'])) {
      $this->baseUrl = $parameters['base_url'];
    }
    if (isset($parameters['log_types'])) {
      self::$logTypes = $parameters['log_types'];
    }
    if (isset($parameters['log_levels'])) {
      $this->setLevels($parameters['log_levels']);
    }

  }

  /**
   * Set logs levels.
   *
   * @param array $levels_list
   *   Levels list.
   */
  protected function setLevels(array $levels_list) {
    foreach ($levels_list as $level) {
      if (defined('RfcLogLevel::' . $level)) {
        self::$logLevels["$level"] = RfcLogLevel::$level;
      }
    }
  }

  /**
   * Set when the suite starts to retrieve the right logs.
   *
   * @BeforeSuite
   */
  public static function setLogsTimeSuite(BeforeSuiteScope $before_suite_scope) {
    self::$suiteStartTime = time();
  }

  /**
   * Show a table list with the logs grouped.
   *
   * @AfterSuite
   */
  public static function showLogsAfterSuite(AfterSuiteScope $after_suite_scope) {

    $grouped_logs = self::getGroupedLogs();
    $table = new Table(new ConsoleOutput());
    $table->setHeaderTitle('Watchdog errors');
    $table->setHeaders(['Type', 'Severity', 'Message', 'Total Messages']);

    $levels = RfcLogLevel::getLevels();
    foreach ($grouped_logs as $log) {
      $message = self::formatMessageWatchdog($log);
      $severity = property_exists($log, 'severity') && isset($levels[$log->severity]) ? $levels[$log->severity] : '';
      $type = property_exists($log, 'type') ? $log->type : '';
      $count = property_exists($log, 'watchdog_message_count') ? $log->watchdog_message_count : '';
      $table->addRow(["[{$type}]", $severity, $message, $count]);
    }

    $table->render();
  }

  /**
   * Get the logs grouped.
   *
   * @return array
   *   List of logs.
   */
  public static function getGroupedLogs() {
    $core = self::getStaticCore();
    $method = $core . "::getDbLogGroupedMessages";
    $logs = call_user_func($method, self::$suiteStartTime, self::$logLevels, self::$logTypes);
    return $logs;
  }

  /**
   * Returns the current Drupal core helper.
   *
   * @return string
   *   Drupal core class.
   */
  protected static function getStaticCore() {
    if (!isset(self::$core)) {
      $version = self::getDrupalVersion();
      $core = "\Metadrop\Behat\Cores\Drupal$version";
      self::$core = $core;
    }
    return self::$core;
  }

  /**
   * Show watchdog logs messages after scenario.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   After Scenario scope.
   *
   * @AfterScenario @api
   */
  public function showDbLog(AfterScenarioScope $scope) {
    $module_is_enabled = in_array('dblog', $this->getCore()->getModuleList());

    if ($module_is_enabled) {
      $log_types = $scope->getTestResult()->getResultCode() === TestResults::PASSED ? self::$logTypes : [];
      // Filter by error, notice, and warning severity.
      $logs = $this->getCore()->getDbLogMessages($this->getScenarioStartTime(), self::$logLevels, $log_types);
      if (!empty($logs)) {
        $this->printWatchdogLogs($logs);
      }
    }
  }

  /**
   * Print logs from watchdog.
   *
   * @param array $logs
   *   List of objects containing the message, the type, and the variables.
   */
  public function printWatchdogLogs(array $logs) {
    print 'Logs from watchdog (dblog):' . PHP_EOL . PHP_EOL;
    foreach ($logs as $log) {
      $message = self::formatMessageWatchdog($log);
      print "[{$log->type}] "
          . $message
          . " | Details: " . $this->getDblogEventUrl($log->wid) . "\n";
    }
    print "End of watchdog logs.";
  }

  /**
   * Format message log.
   *
   * @param object $log
   *   Log.
   *
   * @return string
   *   Formatted log.
   */
  public static function formatMessageWatchdog(\stdClass $log) {
    $log_variables = unserialize($log->variables);
    $log->variables = !empty($log_variables) ? $log_variables : [];
    $core = self::getStaticCore();
    $method = $core . "::formatStringStatic";
    $formatted_string = call_user_func($method, $log->message, $log->variables);
    $message = mb_strimwidth($formatted_string, 0, 200, '...');
    return $message;
  }

  /**
   * Get the log event url.
   *
   * @param int $wid
   *   Watchdog id.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Generated url.
   */
  protected function getDblogEventUrl(int $wid) {
    $options = ['absolute' => TRUE];
    if (!empty($this->baseUrl)) {
      $options['base_url'] = $this->baseUrl;
    }

    // It is not possible to invoke core methods because the way
    // the url generated is not compatible:
    // - In Drupal 7 it's used the relative path.
    // - In Drupal 8 it's used the routing system.
    if ($this->getCore() instanceof Drupal7) {
      return url('/admin/reports/event/' . $wid, $options);
    }
    else {
      return Url::fromRoute('dblog.event', [
        'event_id' => $wid,
      ], $options)->toString();
    }

  }

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal version could not be determined.
   *
   * @see \Drupal\Driver\DrupalDriver::getDrupalVersion
   */
  public static function getDrupalVersion() {
    if (!isset(self::$coreVersion)) {
      // Support 6, 7 and 8.
      $version_constant_paths = [
        // Drupal 6.
        '/modules/system/system.module',
        // Drupal 7.
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/autoload.php',
        '/core/includes/bootstrap.inc',
      ];

      if (DRUPAL_ROOT === FALSE) {
        throw new BootstrapException('`drupal_root` parameter must be defined.');
      }

      foreach ($version_constant_paths as $path) {
        if (file_exists(DRUPAL_ROOT . $path)) {
          require_once DRUPAL_ROOT . $path;
        }
      }
      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }
      else {
        throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, and 8.');
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        self::$coreVersion = (integer) $version_parts[0] < 8 ? $version_parts[0] : 8;
      }
      else {
        throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }
    return self::$coreVersion;
  }

}
