<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Unable to Read file.
 */
class UnableToReadException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Unable to read file';

}
