<?php

namespace Drupal\flysystem\FlyStream\Exception;

/**
 * Exception handler when File is Not Found.
 */
class FileNotFoundException extends StreamWrapperException {
  protected const ERROR_MESSAGE = 'No such file or directory';

}
