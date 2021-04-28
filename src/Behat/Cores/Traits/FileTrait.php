<?php

namespace Metadrop\Behat\Cores\Traits;

/**
 * Trait FileTrait.
 */
trait FileTrait {

  /**
   * Creates file in drupal.
   *
   * @param string $file_path
   *   Absolute path to file.
   * @param string directory
   *   A string containing the files scheme, usually "public://".
   */
  public function createFileWithName($file_path, $directory = NULL) {

    if (empty($directory)) {
      $directory = $this->getDefaultFileScheme();
    }

    $destination = $directory . '/' . basename($file_path);

    if (!file_exists($file_path)) {
      throw new \Exception("Error: file " . basename($file_path) . " not found");
    }
    else {
      $data = file_get_contents($file_path);
      // Existing files are replaced.
      if (!$file) {
        throw new \Exception("Error: file could not be copied to directory");
      }
    }
    return $this->entityId('file', $file);
  }

  /**
   * Get the default file scheme.
   *
   * @return string
   *   Default file scheme.
   */
  abstract public function getDefaultFileScheme();

}
