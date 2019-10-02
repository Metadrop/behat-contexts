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

  /**
   * Get path for file in drupal 8.
   *
   * @param string $filename
   *   The name of the file to get.
   * @param string directory
   *   A string containing the files scheme, usually "public://".
   *
   * @throws Exception
   *   Exception file not found.
   *
   * @throws Exception
   *   Exception file could not be copied.
   *
   * @Given file get name :filename
   * @Given file get name :filename in the :directory directory
   */
  public function visitFileWithName($filename, $directory = NULL) {
    $public = 'public://';
    $private = 'private://';

    if (empty($directory) || $directory === $public) {
      $realpath = \Drupal::service('file_system')->realpath($directory);
      $path = str_replace(DRUPAL_ROOT, '', $realpath);
      $destination = $path . '/' . basename($filename);
    }

    if (!empty($directory) && $directory === $private) {
      $destination = \Drupal\Core\Url::fromRoute('system.private_file_download', ['filepath' => $filename], [
        'relative' => TRUE,
      ])->toString();
    }

    $this->visitPath($destination);
  }
  
}
