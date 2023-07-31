<?php

namespace Drupal\flysystem\Exception;

/**
 * Ported from twistor/flysystem-stream-wrapper.
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Exception/DirectoryNotEmptyException.php.
 */
class DirectoryNotEmptyException extends TriggerErrorException
{
    protected $defaultMessage = '%s(): Directory not empty';
}