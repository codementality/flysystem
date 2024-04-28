<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when File is Not Found.
 */
class IsDirectoryException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Is a directory';

}
