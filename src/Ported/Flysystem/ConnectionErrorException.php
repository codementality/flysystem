<?php

namespace Drupal\flysystem\Ported\Flysystem;

use ErrorException;

/**
 * Ported from league/flysystem v1
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/ConnectionErrorException.php.
 */
class ConnectionErrorException extends ErrorException implements FilesystemException
{
}