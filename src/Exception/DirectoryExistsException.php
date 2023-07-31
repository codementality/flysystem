<?php

namespace Drupal\flysystem\Exception;

/**
 * Ported from twistor/flysystem-stream-wrapper.
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Exception/DirectoryExistsException.php.
 */
class DirectoryExistsException extends TriggerErrorException
{
    protected $defaultMessage = '%s(): Is a directory';
}