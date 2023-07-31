<?php

namespace Drupal\flysystem\Exception;

/**
 * Ported from twistor/flysystem-stream-wrapper.
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Exception/NotADirectoryException.php.
 */
class NotADirectoryException extends TriggerErrorException
{
    protected $defaultMessage = '%s(): Not a directory';
}