<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when Directory is not found.
 */
class DirectoryNotFoundException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'Failed to open dir';

}
