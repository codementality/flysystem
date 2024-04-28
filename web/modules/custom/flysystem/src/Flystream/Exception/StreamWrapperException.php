<?php

namespace Drupal\flysystem\FlyStream\Exception;

use League\Flysystem\FilesystemException;

/**
 * StreamWrapper exception handler.
 */
class StreamWrapperException extends \RuntimeException implements FilesystemException {
  protected const ERROR_MESSAGE = 'Error message not defined';

  /**
   * Returns meta information for exception.
   *
   * @param string $command
   *   Command triggering exception.
   * @param string $location
   *   Code location where exception was triggered.
   * @param \Throwable $previous
   *   Previous exception.
   *
   * @return \Drupal\flysystem\FlyStream\Exception\StreamWrapperException
   *   Exception thrown.
   */
  public static function atLocation(
    string $command,
    string $location,
    \Throwable $previous = NULL,
  ): StreamWrapperException {
    return new self("$command($location): " . static::ERROR_MESSAGE, 0, $previous);
  }

}
