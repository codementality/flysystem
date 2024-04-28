<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory Could Not be Removed.
 */
class CouldNotRemoveDirectoryException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Could not remove directory';

}
