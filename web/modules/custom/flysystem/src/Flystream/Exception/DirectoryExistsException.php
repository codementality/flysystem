<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory exists.
 */
class DirectoryExistsException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Directory exists';

}
