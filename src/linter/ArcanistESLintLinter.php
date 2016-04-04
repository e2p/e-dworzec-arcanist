<?php

/**
 * Uses ESLint - the pluggable linting utility for JavaScript and JSX
 */
final class ArcanistESLintLinter extends ArcanistExternalLinter {

  public function getLinterName() {
    return 'ESLint - the pluggable linting utility for JavaScript and JSX';
  }

  public function getLinterConfigurationName() {
    return 'eslint';
  }

  public function getDefaultBinary() {
    return './node_modules/.bin/eslint';
  }

  public function getInstallInstructions() {
    return pht(
      'Install eslint and other project dependencies using `%s`.',
      'npm install');
  }

  public function getInfoURI() {
    return 'http://eslint.org/';
  }

  protected function getDefaultMessageSeverity($code) {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  protected function getMandatoryFlags() {
    return array(
      '--format',
      'json',
    );
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $result = (new PhutilJSONParser())->parse($stdout);

    $messages = array();
    foreach ($result[0]['messages'] as $msg) {
      $message = new ArcanistLintMessage();
      $message->setPath($path);
      $message->setLine(@$msg['line']);
      $message->setChar(@$msg['column']);
      $message->setCode(@$msg['ruleId']);
      $message->setName($msg['message']." (eslint)");
      $message->setSeverity(
        $msg['severity'] == '1'
          ? ArcanistLintSeverity::SEVERITY_WARNING
          : ArcanistLintSeverity::SEVERITY_ERROR);

      $messages[] = $message;
    }

    return $messages;
  }
}
