<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when File Could Not be Deleted.
 */
class CouldNotDeleteFileException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Could not delete file';

}
