<?php

namespace Drupal\flysystem\Ported;

/**
 * Ported from twistor/flysystem-stream-wrapper. 
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Uid.php.
 */
class Uid
{
    public function getUid()
    {
        return (int) getmyuid();
    }

    public function getGid()
    {
        return (int) getmygid();
    }
}