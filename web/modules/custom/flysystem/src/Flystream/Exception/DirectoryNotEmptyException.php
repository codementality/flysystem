<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory is Not Empty.
 */
class DirectoryNotEmptyException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Directory not empty';

}
