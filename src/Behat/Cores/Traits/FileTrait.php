<?php

namespace Metadrop\Behat\Cores\Traits;

use Drupal\Core\File\FileSystemInterface;

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
      $directory = \Drupal::config('system.file')
          ->get('default_scheme') . '://';
    }

    $destination = $directory . '/' . basename($file_path);

    if (!file_exists($file_path)) {
      throw new \Exception("Error: file " . basename($file_path) . " not found");
    }
    else {
      $data = file_get_contents($file_path);
      $file = file_save_data($data, $destination, FileSystemInterface::EXISTS_REPLACE);
      if (!$file) {
        throw new \Exception("Error: file could not be copied to directory");
      }
    }
    return $this->entityId('file', $file);
  }

}
