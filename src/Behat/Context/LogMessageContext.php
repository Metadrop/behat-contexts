<?php

namespace Metadrop\Behat\Context;

use Metadrop\Behat\Cores\Traits\ScenarioTimeTrait;
use Behat\Testwork\Tester\Result\TestResults;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

class LogMessageContext extends RawDrupalContext {

  use ScenarioTimeTrait;

  /**
   * Show watchdog logs messages after scenario.
   *
   * @param AfterScenarioScope $scope
   *   After Scenario scope.
   *
   * @AfterScenario @api
   */
  public function showDbLog(AfterScenarioScope $scope) {

    // If scenario passed only php warnings and notices will be shown.
    $hasPassed = $scope->getTestResult()->getResultCode() === TestResults::PASSED;
    $core = $this->getCore();
    $module_is_enabled = in_array('dblog', $core->getModuleList());

    if ($module_is_enabled) {
      $logs = $core->getDbLogMessages($this->getScenarioStartTime(), $hasPassed);
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
    print 'Logs from watchdog (dblog):' . "\n\n";
    foreach ($logs as $log) {
      $log_variables = unserialize($log->variables);
      $log->variables = !empty($log_variables) ? $log_variables : [];
      print "[{$log->type}] "
          . format_string($log->message, $log->variables)
          . " | Details: " . $this->getCore()->getDblogEventUrl($log->wid);
    }
    print "End of watchdog logs.";
  }

}
