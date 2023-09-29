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
      $directory = $this->getDefaultFileScheme();
    }

    $destination = $directory . '/' . basename($file_path);

    if (!file_exists($file_path)) {
      throw new \Exception("Error: file " . basename($file_path) . " not found");
    }
    else {
      $data = file_get_contents($file_path);

      // Existing files are replaced.
      $file = $this->fileSaveData($data, $destination, 1);
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

  /**
   * Saves a file to the specified destination and creates a database entry.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param string|null $destination
   *   (optional) A string containing the destination URI. This must be a stream
   *   wrapper URI. If no value or NULL is provided, a randomized name will be
   *   generated and the file will be saved using Drupal's default files scheme,
   *   usually "public://".
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   A file entity, or FALSE on error.
   *
   * @see \Drupal\Core\File\FileSystemInterface::saveData()
   */
  abstract public function fileSaveData(string $data, $destination = NULL, int $replace = FileSystemInterface::EXISTS_RENAME);

}
