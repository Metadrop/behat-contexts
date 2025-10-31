<?php

namespace Metadrop\Behat\Context;

use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Hook\AfterScenario;
use Drupal\Core\Url;
use Metadrop\Behat\Cores\Traits\ScenarioTimeTrait;
use Behat\Testwork\Tester\Result\TestResults;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeSuite;
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
  protected static $baseUrl;

  /**
   * Log types.
   *
   * @var array
   */
  protected static $types = ['php'];

  /**
   * Log levels to register.
   *
   * @var array
   */
  protected static $levels = [
    'ERROR' => 3,
    'WARNING' => 4,
    'NOTICE' => 5,
  ];

  /**
   * Limit of log shown on the table after suite.
   *
   * @var int
   */
  protected static $limit = 100;

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
   * Log csv path.
   *
   * @var string
   */
  protected static $path = DRUPAL_ROOT . '/../reports/behat/dblog';

  /**
   * Option to enable, disable to write the report.
   *
   * @var bool
   */
  protected static $writeReportEnabled = FALSE;

  /**
   * LogsContext constructor.
   *
   * @param array $parameters
   *   Parameters (optional).
   */
  public function __construct(array $parameters = []) {

    if (isset($parameters['base_url'])) {
      static::$baseUrl = $parameters['base_url'];
    }
    if (isset($parameters['types'])) {
      static::$types = $parameters['types'];
    }
    if (isset($parameters['levels'])) {
      $this->setLevels($parameters['levels']);
    }
    if (isset($parameters['limit'])) {
      static::$limit = $parameters['limit'];
    }
    if (isset($parameters['path'])) {
      static::$path = $parameters['path'];
    }
    if (isset($parameters['write_report'])) {
      static::$writeReportEnabled = $parameters['write_report'];
    }
  }

  /**
   * Set logs levels.
   *
   * @param array $levels_list
   *   Levels list.
   */
  protected function setLevels(array $levels_list) {
    static::$levels = [];
    foreach ($levels_list as $level) {

      $constant_name = '\Drupal\Core\Logger\RfcLogLevel::' . $level;
      if (defined($constant_name)) {
        static::$levels[$level] = constant($constant_name);
      }
    }
  }

  /**
   * Set when the suite starts to retrieve the right logs.
   */
  #[\Behat\Hook\BeforeSuite]
  public static function setLogsTimeSuite(BeforeSuiteScope $before_suite_scope) {
    static::$suiteStartTime = time();
  }

  /**
   * Process logs after behat suite.
   */
  #[\Behat\Hook\AfterSuite]
  public static function showLogsAfterSuite(AfterSuiteScope $after_suite_scope) {
    $grouped_logs = static::getGroupedLogs();
    if (!empty($grouped_logs)) {
      static::writeTableLogs($grouped_logs);
      if (static::$writeReportEnabled) {
        static::writeReport($grouped_logs);
      }
    }

  }

  /**
   * Show a table list with the logs grouped.
   *
   * @param array $grouped_logs
   *   List of logs to show.
   */
  public static function writeTableLogs(array $grouped_logs) {
    $table = new Table(new ConsoleOutput());
    $table->setHeaderTitle('Watchdog errors');
    $table->setHeaders([
      'Index',
      'Type',
      'Severity',
      'Message',
      'Details',
      'Total Messages',
    ]);

    $levels = RfcLogLevel::getLevels();
    $i = 1;
    $limit = static::$limit;
    $grouped_logs_limit = array_slice($grouped_logs, 0, $limit);
    foreach ($grouped_logs_limit as $log) {
      $message = static::formatMessageWatchdog($log);
      $event_url = property_exists($log, 'wid') ? static::getDblogEventUrl($log->wid) : '';
      $severity = property_exists($log, 'severity') && isset($levels[$log->severity]) ? $levels[$log->severity] : '';
      $type = property_exists($log, 'type') ? $log->type : '';
      $count = property_exists($log, 'watchdog_message_count') ? $log->watchdog_message_count : '';
      $table->addRow([$i, "[{$type}]", $severity, $message, $event_url, $count]);
      $i++;
    }

    $table->render();
  }

  /**
   * Write report into csv.
   *
   * @param array $grouped_logs
   *   List of logs to write.
   */
  public static function writeReport(array $grouped_logs) {
    // Add date to report.
    $source_dir = static::$path;
    $date = date_create();
    $time = date_format($date, "Y-m-d-H-i-s");
    $source_file = $source_dir . '/dblog-report-' . $time . '.csv';
    if (!file_exists($source_dir)) {
      mkdir($source_dir, 0777, TRUE);
    }
    if (is_writable($source_dir)) {
      // Open CSV.
      $stream = fopen($source_file, 'w+');

      // Write header.
      fputcsv($stream, [
        'Index',
        'Type',
        'Severity',
        'Message',
        'Location',
        'Referer',
        'Link',
        'Details',
        'Total Messages',
      ]);

      $i = 1;
      $levels = RfcLogLevel::getLevels();
      foreach ($grouped_logs as $log) {
        $message = static::formatMessageWatchdog($log);
        $event_url = property_exists($log, 'wid') ? static::getDblogEventUrl($log->wid) : '';
        $severity = property_exists($log, 'severity') && isset($levels[$log->severity]) ? $levels[$log->severity] : '';
        $type = property_exists($log, 'type') ? $log->type : '';
        $count = property_exists($log, 'watchdog_message_count') ? $log->watchdog_message_count : '';
        $location = property_exists($log, 'location') ? $log->location : '';
        $referer = property_exists($log, 'referer') ? $log->referer : '';
        $link = property_exists($log, 'link') ? $log->link : '';

        // Write into the CSV.
        fputcsv($stream, [
          $i,
          $type,
          $severity,
          $message,
          $location,
          $referer,
          $link,
          $event_url,
          $count,
        ]);
        $i++;

      }

      // Close file.
      fclose($stream);
      $output = new ConsoleOutput();
      $output->writeln('Created dblog report on ' . $source_file);
    }
  }

  /**
   * Get the logs grouped.
   *
   * @return array
   *   List of logs.
   */
  public static function getGroupedLogs() {
    $core = static::getStaticCore();
    $method = $core . "::getDbLogGroupedMessages";
    $logs = call_user_func($method, static::$suiteStartTime, static::$levels, static::$types);
    return $logs;
  }

  /**
   * Returns the current Drupal core helper.
   *
   * @return string
   *   Drupal core class.
   */
  protected static function getStaticCore() {
    if (!isset(static::$core)) {
      $version = static::getDrupalVersion();
      $core = "\Metadrop\Behat\Cores\Drupal$version";
      static::$core = $core;
    }
    return static::$core;
  }

  /**
   * Show watchdog logs messages after scenario.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   After Scenario scope.
   */
  #[AfterScenario('@api')]
  public function showDbLog(AfterScenarioScope $scope) {
    $module_is_enabled = in_array('dblog', $this->getCore()->getModuleList());

    if ($module_is_enabled) {
      $log_types = $scope->getTestResult()->getResultCode() === TestResults::PASSED ? static::$types : [];
      // Filter by error, notice, and warning severity.
      $logs = $this->getCore()->getDbLogMessages($this->getScenarioStartTime(), static::$levels, $log_types);
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
      $message = static::formatMessageWatchdog($log);
      print "[{$log->type}] "
          . $message
          . " | Details: " . static::getDblogEventUrl($log->wid) . "\n";
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
    $log_variables = property_exists($log, 'variables') && is_string($log->variables) && !empty($log->variables) ? unserialize($log->variables) : [];
    $message = property_exists($log, 'message') ? $log->message : '';
    $core = static::getStaticCore();
    $method = $core . "::formatStringStatic";
    $formatted_string = call_user_func($method, $message, $log_variables);
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
  protected static function getDblogEventUrl(int $wid) {
    $options = ['absolute' => TRUE];
    if (!empty(static::$baseUrl)) {
      $options['base_url'] = static::$baseUrl;
    }

    // It is not possible to invoke core methods because the way
    // the url generated is not compatible:
    // - In Drupal 7 it's used the relative path.
    // - In Drupal 8 it's used the routing system.
    if (static::getDrupalVersion() == 7) {
      return url('/admin/reports/event/' . $wid, $options);
    }
    else {
      return Url::fromRoute('dblog.event', [
        'event_id' => $wid,
      ], $options)->toString();
    }

  }

}
