<?php

namespace Metadrop\Behat\Context;

use Drupal\file\FileInterface;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\When;


class FileContext extends RawDrupalContext {

  /**
   * Array of files to be cleaned up @AfterScenario.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Deletes Files after each Scenario.
   */
  #[AfterScenario]
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
   */
  #[Given('file with name :filename')]
  #[Given('file with name :filename in the :directory directory')]
  public function createFileWithName($filename, $directory = NULL) {
    $absolutePath = $this->getMinkParameter('files_path');
    $path = $absolutePath . '/' . $filename;

    $this->files[] = $this->getCore()->createFileWithName($path, $directory);
  }

  /**
   * Get path for file.
   *
   * @param string $filename
   *   The name of the file to get.
   * @param string $directory
   *   A string containing the files scheme, usually "public://".
   *
   * @throws Exception
   *   Exception file not found.
   *
   * @throws Exception
   *   Exception destination not found.
   */
  #[Given('I visit file with name :filename')]
  #[Given('I visit file with name :filename in the :directory directory')]
  public function visitFileWithName($filename, $directory = NULL) {
    $destination = $this->getCore()->getFileDestination($filename, $directory);

    if ($destination === NULL) {
      throw new \RuntimeException('Could not set the correct destination.');
    }

    $this->visitPath($destination);
  }

  #[When('I go to the view the file with name :arg1')]
  public function iGoToTheViewTheFileWithName($filename)
  {
    $file = $this->getCore()->loadEntityByLabel('file', $filename);
    if ($file instanceof FileInterface) {
      $this->visitPath($this->getCore()->createFileUrl($file));
    }
    else {
      throw new \Exception(sprintf('File with name %s not found.', $filename));
    }
  }

}
