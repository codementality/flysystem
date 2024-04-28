<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Unable to Write to file.
 */
class UnableToWriteException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Unable to write to file';

}
