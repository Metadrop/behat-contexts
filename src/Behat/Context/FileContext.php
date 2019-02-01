<?php

namespace Metadrop\Behat\Context;

class FileContext extends RawDrupalContext {
  
 /**
   * Array of files to be cleaned up @AfterScenario.
   *
   * @var array
   */
  protected $files = array();

  /**
   * Deletes Files after each Scenario.
   *
   * @AfterScenario
   */
  public function cleanFiles() {
    foreach ($this->files as $k => $v) {
      $this->getCore()->fileDelete($v);
    }
  }

  /**
   * Creates file in drupal.
   *
   * @param string $filename
   *   The name of the file to create.
   * @param string directory
   *   A string containing the files scheme, usually "public://".
   *
   * @throws Exception
   *   Exception file not found.
   *
   * @throws Exception
   *   Exception file could not be copied.
   *
   * @Given file with name :filename
   * @Given file with name :filename in the :directory directory
   */
  public function createFileWithName($filename, $directory = NULL) {
    $absolutePath = $this->getMinkParameter('files_path');
    $path = $absolutePath . '/' . $filename;

    $this->files[] = $this->getCore()->createFileWithName($path, $directory);
  }

}