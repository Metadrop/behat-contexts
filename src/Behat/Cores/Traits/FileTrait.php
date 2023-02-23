<?php

namespace Metadrop\Behat\Cores\Traits;

/**
 * Trait FileTrait.
 */
trait FileTrait {

  /**
   * The string translation service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

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
      $file = $this->getFileRepository()->writeData($data, $destination, 1);
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
   * Gets the file repository service.
   *
   * @return \Drupal\file\FileRepositoryInterface
   *   The file repository service.
   */
  protected function getFileRepository() {
    if (!$this->fileRepository) {
      $this->fileRepository = \Drupal::service('file.repository');
    }

    return $this->fileRepository;
  }

}
