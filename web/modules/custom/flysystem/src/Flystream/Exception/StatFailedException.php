<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory is the root directory.
 */
class StatFailedException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Stat failed';

}
