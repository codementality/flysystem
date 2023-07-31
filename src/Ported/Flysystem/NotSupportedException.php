<?php

namespace Drupal\flysystem\Ported\Flysystem;

use RuntimeException;
use SplFileInfo;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/NotSupportedException.php.
 */
class NotSupportedException extends RuntimeException implements FilesystemException
{
    /**
     * Create a new exception for a link.
     *
     * @param SplFileInfo $file
     *
     * @return static
     */
    public static function forLink(SplFileInfo $file)
    {
        $message = 'Links are not supported, encountered link at ';

        return new static($message . $file->getPathname());
    }

    /**
     * Create a new exception for a link.
     *
     * @param string $systemType
     *
     * @return static
     */
    public static function forFtpSystemType($systemType)
    {
        $message = "The FTP system type '$systemType' is currently not supported.";

        return new static($message);
    }
}