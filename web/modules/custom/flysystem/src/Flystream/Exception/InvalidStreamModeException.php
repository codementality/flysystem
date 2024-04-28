<?php

namespace Drupal\flysystem\FlyStream\Exception;

use League\Flysystem\FilesystemException;

/**
 * Exception handler when stream mode is not valid.
 */
class InvalidStreamModeException extends \RuntimeException implements FilesystemException {

  /**
   * Returns meta information for exception.
   *
   * @param string $command
   *   Command triggering exception.
   * @param string $location
   *   Code location where exception was triggered.
   * @param string $mode
   *   File / Directory mode.
   * @param \Throwable $previous
   *   Previous exception.
   *
   * @return \Drupal\flysystem\FlyStream\Exception\InvalidStreamModeException
   *   Exception thrown.
   */
  public static function atLocation(
    string $command,
    string $location,
    string $mode,
    \Throwable $previous = NULL,
  ): InvalidStreamModeException {
    return new self(
      "$command($location): Failed to open stream: '$mode' is not a valid mode",
      0,
      $previous
    );
  }

}
