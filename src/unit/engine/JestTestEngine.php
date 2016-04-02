<?php

final class JestTestEngine extends ArcanistUnitTestEngine {

  public function getEngineConfigurationName() {
    return 'jest';
  }

  public function run() {
    $interpreter = 'npm';
    $command = $interpreter.' test';

    $future = new ExecFuture($command);
    list($err, $stdout, $stderr) = $future->resolve();

    $result = new ArcanistUnitTestResult();
    $result->setName($command);
    $result->setResult(
      $err
        ? ArcanistUnitTestResult::RESULT_FAIL
        : ArcanistUnitTestResult::RESULT_PASS
    );
    $result->setUserData($stdout);
    return array($result);
  }

}
