<?php

namespace Drupal\flysystem\FlyStream\Exception;

use League\Flysystem\FilesystemException;

/**
 * Exception handler when Unable to change permissions.
 */
class UnableToChangePermissionsException extends \RuntimeException implements FilesystemException {

  /**
   * Returns meta information for exception.
   *
   * @param string $command
   *   Command triggering exception.
   * @param string $location
   *   Code location where exception was triggered.
   * @param string $permission
   *   File / Directory permission.
   * @param \Throwable $previous
   *   Previous exception.
   *
   * @return \Drupal\flysystem\FlyStream\Exception\UnableToChangePermissionsException
   *   Exception thrown.
   */
  public static function atLocation(
    string $command,
    string $location,
    string $permission,
    \Throwable $previous = NULL,
  ): UnableToChangePermissionsException {
    return new self("$command($location,$permission): Unable to change permissions", 0, $previous);
  }

}
