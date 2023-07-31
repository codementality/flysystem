<?php

namespace Drupal\flysystem\Ported;

/**
 * Ported from twistor/flysystem-stream-wrapper. 
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/PosixUid.php.
 */
class PosixUid extends Uid
{
    public function getUid()
    {
        return (int) posix_getuid();
    }

    public function getGid()
    {
        return (int) posix_getuid();
    }
}
