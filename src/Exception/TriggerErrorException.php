<?php

namespace Drupal\flysystem\Exception;

use Drupal\flysystem\Ported\Flysystem\Exception;

/**
 * Ported from twistor/flysystem-stream-wrapper.
 * @see https://github.com/twistor/flysystem-stream-wrapper/blob/master/src/Flysystem/Exception/TriggerErrorException.php.
 */
class TriggerErrorException extends Exception
{
    protected $defaultMessage;

    public function formatMessage($function)
    {
        return sprintf($this->message ? $this->message : $this->defaultMessage, $function);
    }
}