<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when File is Not Found.
 */
class IsNotDirectoryException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Not a directory';

}
