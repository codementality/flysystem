<?php

namespace Drupal\flysystem\Ported\Flysystem;

use RuntimeException;

/**
 * Ported from league/flysystem v1
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/ConnectionRuntimeException.php.
 */
class ConnectionRuntimeException extends RuntimeException implements FilesystemException
{
}