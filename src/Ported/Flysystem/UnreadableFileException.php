<?php

namespace Drupal\flysystem\Ported\Flysystem;

use SplFileInfo;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/UnreadableFileException.php.
 */
class UnreadableFileException extends Exception
{
    public static function forFileInfo(SplFileInfo $fileInfo)
    {
        return new static(
            sprintf(
                'Unreadable file encountered: %s',
                $fileInfo->getRealPath()
            )
        );
    }
}