<?php

/**
 * Uses Facebook's Flow to statically check JavaScript code.
 * http://flowtype.org/
 */
final class ArcanistFlowLinter extends ArcanistLinter {

  protected $didRun = false;
  protected $expectedVersion = null;

  public function getLinterName() {
    return 'Flow';
  }

  public function getLinterConfigurationName() {
    return 'flow';
  }

  public function getInfoDescription() {
    return 'A static type checker for JavaScript';
  }

  public function getInfoURI() {
    return 'http://flowtype.org/';
  }

  public function lintPath($path) {
    if ($this->didRun) {
      return;
    }
    $this->didRun = true;

    $interpreter = 'npm';

    if (!Filesystem::binaryExists($interpreter)) {
      throw new ArcanistMissingLinterException(
        pht(
          'Unable to locate interpreter "%s" to run linter %s. You may need '.
          'to install the interpreter, or adjust your linter configuration.',
          $interpreter,
          get_class($this)));
    }

    $future = new ExecFuture('%C %C', $interpreter, 'run flow --loglevel silent -- version');
    list($version, $_) = $future->resolvex();
    if (
      $this->expectedVersion &&
      strpos($version, $this->expectedVersion) === false
    ) {
      throw new ArcanistMissingLinterException(
        pht(
          'Your interpreter "%s" is not in the expected version "%s" to run '.
          'linter %s. You may need to update the interpreter, or adjust your '.
          'linter configuration.',
          $interpreter,
          $this->expectedVersion,
          get_class($this)));
    }

    $future = new ExecFuture('%C %C', $interpreter, 'run flow --loglevel silent -- check --json');
    list($err, $stdout, $stderr) = $future->resolve();

    $result = (new PhutilJSONParser())->parse($stdout);
    foreach ($result['errors'] as $error) {
      $message = new ArcanistLintMessage();
      $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);
      $message->setCode($error['kind']);

      $msgs = $error['message'];
      $message->setPath($msgs[0]['path']);
      $message->setLine($msgs[0]['line']);
      $message->setChar($msgs[0]['start']);
      $message->setName($msgs[0]['descr']." (flow)");

      $projectRoot = $this->getProjectRoot();

      $description = "";
      for ($i = 1, $count = count($msgs); $i < $count; $i++) {
        $msg = $msgs[$i];

        if ($msg['path']) {
          $description .= Filesystem::readablePath($msg['path'], $projectRoot);
          $description .= ":".$msg['line'];
          $description .= ":".$msg['start'].",".$msg['end'].": ";
        }
        $description .= $msg['descr'] . "\n";
      }
      $message->setDescription($description);

      $this->addLintMessage($message);
    }
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'version' => array(
        'type' => 'optional string',
        'help' => pht(
          'Specify a string identifying the version of flow '
        ),
      ),
    );
    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'version':
        $this->expectedVersion = $value;
        return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }
}
