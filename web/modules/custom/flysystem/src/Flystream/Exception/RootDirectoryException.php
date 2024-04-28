<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory is the root directory.
 */
class RootDirectoryException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Directory is root';

}
