<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory cannot be created.
 */
class UnableToCreateDirectoryException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Cannot create directory';

}
